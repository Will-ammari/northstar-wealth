<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordTransaction
{
    /**
     * All quantity arithmetic is DECIMAL inside MySQL, never PHP floats.
     * Asset row lock serializes changes to one holding. The (portfolio, key) UNIQUE
     * constraint is the final guard for parallel requests, including different assets.
     */
    public function execute(Portfolio $portfolio, Asset $asset, User $actor, array $data): PortfolioTransaction
    {
        try {
            return DB::transaction(function () use ($portfolio, $asset, $actor, $data): PortfolioTransaction {
                $existing = $this->findReplay($portfolio, $data['idempotency_key']);
                if ($existing) {
                    return self::assertMatchingReplay($existing, $asset, $data);
                }

                // Lock a unique asset row, not a whole portfolio (different assets may update concurrently).
                $locked = Asset::where('portfolio_id', $portfolio->id)
                    ->whereKey($asset->id)->lockForUpdate()->firstOrFail();

                // This read is AFTER the locking read to see a concurrent commit.
                $existing = $this->findReplay($portfolio, $data['idempotency_key']);
                if ($existing) {
                    return self::assertMatchingReplay($existing, $asset, $data);
                }

                $op = $data['type'] === 'buy' ? '+' : '-';
                $sql = "UPDATE assets SET quantity = quantity {$op} CAST(? AS DECIMAL(24,8)), updated_at = ? "
                    .'WHERE id = ? AND portfolio_id = ?';
                $bindings = [$data['quantity'], now(), $locked->id, $portfolio->id];
                if ($data['type'] === 'sell') {
                    $sql .= ' AND quantity >= CAST(? AS DECIMAL(24,8))';
                    $bindings[] = $data['quantity'];
                }
                if (DB::update($sql, $bindings) !== 1) {
                    throw ValidationException::withMessages(['quantity'=>'Insufficient holding quantity.']);
                }

                return PortfolioTransaction::create([
                    'portfolio_id'=>$portfolio->id, 'asset_id'=>$asset->id, 'actor_id'=>$actor->id,
                    'type'=>$data['type'], 'quantity'=>$data['quantity'], 'unit_price'=>$data['unit_price'],
                    'idempotency_key'=>$data['idempotency_key'], 'executed_at'=>now(),
                ]);
            }, attempts: 3);
        } catch (QueryException $exception) {
            // A simultaneous request against *another* asset can win the same key.
            // InnoDB rejects this INSERT; our transaction rolls back its quantity UPDATE.
            // Re-fetch the committed winner and compare ALL payload fields.
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $existing = $this->findReplay($portfolio, $data['idempotency_key']);
                if ($existing) {
                    return self::assertMatchingReplay($existing, $asset, $data);
                }
            }
            throw $exception;
        }
    }

    private function findReplay(Portfolio $portfolio, string $key): ?PortfolioTransaction
    {
        return PortfolioTransaction::where('portfolio_id', $portfolio->id)
            ->where('idempotency_key', $key)->first();
    }

    private static function assertMatchingReplay(PortfolioTransaction $existing, Asset $asset, array $data): PortfolioTransaction
    {
        if ((int) $existing->asset_id !== (int) $asset->id || $existing->type !== $data['type'] ||
            (string) $existing->quantity !== self::canonicalDecimal((string) $data['quantity']) ||
            (string) $existing->unit_price !== self::canonicalDecimal((string) $data['unit_price'])) {
            throw ValidationException::withMessages([
                'idempotency_key'=>'This key was previously used with a different transaction.',
            ]);
        }
        return $existing;
    }

    private static function canonicalDecimal(string $value): string
    {
        [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return (ltrim($integer, '0') ?: '0').'.'.str_pad($fraction, 8, '0');
    }
}
