<?php
namespace Tests\Feature;
use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortfolioSecurityTest extends TestCase
{
    use RefreshDatabase;
    private function setupPortfolio(): array
    {
        $owner=User::create(['name'=>'Owner','email'=>'owner@test.local','password'=>'password']);
        $viewer=User::create(['name'=>'Viewer','email'=>'viewer@test.local','password'=>'password']);
        $outsider=User::create(['name'=>'Outsider','email'=>'outsider@test.local','password'=>'password']);
        $portfolio=Portfolio::create(['name'=>'Private','base_currency'=>'USD','created_by'=>$owner->id]);
        $portfolio->members()->attach($owner->id,['role'=>'owner']);
        $portfolio->members()->attach($viewer->id,['role'=>'viewer']);
        $asset=Asset::create(['portfolio_id'=>$portfolio->id,'type'=>'stock','name'=>'Example','quantity'=>'10','unit_price'=>'25','valued_at'=>now()]);
        return [$owner,$viewer,$outsider,$portfolio,$asset];
    }
    public function test_viewer_cannot_mutate_and_holdings_do_not_change(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio(); Sanctum::actingAs($viewer);
        $this->postJson("/api/portfolios/{$portfolio->id}/assets/{$asset->id}/transactions",[
            'type'=>'sell','quantity'=>'1','unit_price'=>'30','idempotency_key'=>'11111111-1111-4111-8111-111111111111',
        ])->assertForbidden();
        $this->assertSame('10.00000000',(string)$asset->fresh()->quantity);
        $this->assertDatabaseCount('portfolio_transactions',0);
    }
    public function test_outsider_cannot_see_or_edit_portfolio(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio(); Sanctum::actingAs($outsider);
        $this->getJson("/api/portfolios/{$portfolio->id}")->assertForbidden();
        $this->postJson("/api/portfolios/{$portfolio->id}/assets",[
            'type'=>'stock','name'=>'Injected','quantity'=>'1','unit_price'=>'2',
        ])->assertForbidden();
    }
    public function test_owner_cannot_sell_more_than_holdings(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio(); Sanctum::actingAs($owner);
        $this->postJson("/api/portfolios/{$portfolio->id}/assets/{$asset->id}/transactions",[
            'type'=>'sell','quantity'=>'20','unit_price'=>'30','idempotency_key'=>'22222222-2222-4222-8222-222222222222',
        ])->assertUnprocessable();
        $this->assertDatabaseCount('portfolio_transactions',0);
        $this->assertSame('10.00000000',(string)$asset->fresh()->quantity);
    }
    public function test_duplicate_idempotency_key_does_not_double_post(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio(); Sanctum::actingAs($owner);
        $body=['type'=>'buy','quantity'=>'2','unit_price'=>'30','idempotency_key'=>'33333333-3333-4333-8333-333333333333'];
        $uri="/api/portfolios/{$portfolio->id}/assets/{$asset->id}/transactions";
        $this->postJson($uri,$body)->assertCreated(); $this->postJson($uri,$body)->assertCreated();
        $this->assertDatabaseCount('portfolio_transactions',1);
        $this->assertSame('12.00000000',(string)$asset->fresh()->quantity);
    }
    public function test_cross_portfolio_asset_reference_returns_404(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio(); Sanctum::actingAs($owner);
        $second=Portfolio::create(['name'=>'Second','base_currency'=>'USD','created_by'=>$owner->id]);
        $second->members()->attach($owner->id,['role'=>'owner']);
        $this->postJson("/api/portfolios/{$second->id}/assets/{$asset->id}/transactions",[
            'type'=>'buy','quantity'=>'1','unit_price'=>'30','idempotency_key'=>'44444444-4444-4444-8444-444444444444',
        ])->assertNotFound();
    }
    public function test_viewer_cannot_revalue_asset(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio(); Sanctum::actingAs($viewer);
        $this->postJson("/api/portfolios/{$portfolio->id}/assets/{$asset->id}/valuation",[
            'unit_price'=>'9000',
        ])->assertForbidden();
        $this->assertSame('25.00000000',(string)$asset->fresh()->unit_price);
        $this->assertDatabaseCount('asset_valuations',0);
    }
    public function test_transaction_execution_price_does_not_mutate_valuation(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio(); Sanctum::actingAs($owner);
        $this->postJson("/api/portfolios/{$portfolio->id}/assets/{$asset->id}/transactions",[
            'type'=>'buy','quantity'=>'1','unit_price'=>'900','idempotency_key'=>'55555555-5555-4555-8555-555555555555',
        ])->assertCreated();
        $this->assertSame('25.00000000',(string)$asset->fresh()->unit_price);
    }
    public function test_editor_can_record_valuation_and_preserves_history(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio();
        $editor=User::create(['name'=>'Editor','email'=>'editor@test.local','password'=>'password']);
        $portfolio->members()->attach($editor->id,['role'=>'editor']);
        Sanctum::actingAs($editor);
        $this->postJson("/api/portfolios/{$portfolio->id}/assets/{$asset->id}/valuation",[
            'unit_price'=>'42.12345678',
        ])->assertCreated();
        $this->assertDatabaseHas('asset_valuations',[
            'portfolio_id'=>$portfolio->id,'asset_id'=>$asset->id,'recorded_by'=>$editor->id,
        ]);
        $this->assertSame('42.12345678',(string)$asset->fresh()->unit_price);
    }
    public function test_reused_idempotency_uuid_with_different_payload_is_rejected(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio(); Sanctum::actingAs($owner);
        $uri="/api/portfolios/{$portfolio->id}/assets/{$asset->id}/transactions";
        $key='66666666-6666-4666-8666-666666666666';
        $this->postJson($uri,['type'=>'buy','quantity'=>'2','unit_price'=>'10','idempotency_key'=>$key])->assertCreated();
        $this->postJson($uri,['type'=>'buy','quantity'=>'3','unit_price'=>'10','idempotency_key'=>$key])->assertUnprocessable();
        $this->assertDatabaseCount('portfolio_transactions',1);
        $this->assertSame('12.00000000',(string)$asset->fresh()->quantity);
    }
    public function test_unauthenticated_user_cannot_list_portfolios(): void
    {
        $this->getJson('/api/portfolios')->assertUnauthorized();
    }
    public function test_viewer_may_read_dashboard(): void
    {
        [$owner,$viewer,$outsider,$portfolio,$asset]=$this->setupPortfolio(); Sanctum::actingAs($viewer);
        $this->getJson("/api/portfolios/{$portfolio->id}")->assertOk()->assertJsonPath('data.asset_count',1);
    }
}
