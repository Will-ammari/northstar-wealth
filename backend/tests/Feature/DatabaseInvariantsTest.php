<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseInvariantsTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $owner = User::create(['name'=>'Owner', 'email'=>'owner@assessment.test', 'password'=>'secret']);
        $portfolio = Portfolio::create(['name'=>'Test', 'created_by'=>$owner->id, 'base_currency'=>'USD']);
        $asset = Asset::create([
            'portfolio_id'=>$portfolio->id, 'type'=>'stock', 'name'=>'Demo',
            'quantity'=>'5', 'unit_price'=>'25', 'valued_at'=>now(),
        ]);
        return [$owner, $portfolio, $asset];
    }

    public function test_database_rejects_negative_holding_even_outside_controller(): void
    {
        [, , $asset] = $this->fixture();
        $this->expectException(QueryException::class);
        $asset->update(['quantity'=>'-1']);
    }

    public function test_database_rejects_cross_portfolio_transaction_asset_reference(): void
    {
        [$owner, , $asset] = $this->fixture();
        $other = Portfolio::create(['name'=>'Other', 'created_by'=>$owner->id, 'base_currency'=>'USD']);
        $this->expectException(QueryException::class);
        \DB::table('portfolio_transactions')->insert([
            'portfolio_id'=>$other->id, 'asset_id'=>$asset->id, 'actor_id'=>$owner->id,
            'type'=>'buy', 'quantity'=>'1', 'unit_price'=>'5',
            'idempotency_key'=>'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'executed_at'=>now(), 'created_at'=>now(), 'updated_at'=>now(),
        ]);
    }
}
