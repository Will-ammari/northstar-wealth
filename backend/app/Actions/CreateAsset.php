<?php
namespace App\Actions;
use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateAsset
{
    /** Initial quantity and its opening transaction are committed atomically. */
    public function execute(Portfolio $portfolio, User $actor, array $data): Asset
    {
        return DB::transaction(function () use ($portfolio, $actor, $data): Asset {
            $asset = $portfolio->assets()->create([
                'type'=>$data['type'], 'name'=>$data['name'], 'symbol'=>$data['symbol'] ?? null,
                'quantity'=>$data['quantity'], 'unit_price'=>$data['unit_price'], 'valued_at'=>now(),
            ]);
            DB::table('asset_valuations')->insert([
                'portfolio_id'=>$portfolio->id,'asset_id'=>$asset->id,'recorded_by'=>$actor->id,
                'unit_price'=>$data['unit_price'],'valued_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
            ]);
            PortfolioTransaction::create([
                'portfolio_id'=>$portfolio->id, 'asset_id'=>$asset->id, 'actor_id'=>$actor->id,
                'type'=>'buy', 'quantity'=>$data['quantity'], 'unit_price'=>$data['unit_price'],
                'idempotency_key'=>(string) Str::uuid(), 'executed_at'=>now(),
            ]);
            return $asset;
        }, 3);
    }
}
