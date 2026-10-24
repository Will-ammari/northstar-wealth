<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssistantAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_outsider_cannot_trigger_model_permission_check(): void
    {
        $owner = User::create(['name'=>'Owner', 'email'=>'owner@assessment.test', 'password'=>'secret']);
        $outsider = User::create(['name'=>'Outsider', 'email'=>'outsider@assessment.test', 'password'=>'secret']);
        $portfolio = Portfolio::create(['name'=>'Private', 'base_currency'=>'USD', 'created_by'=>$owner->id]);
        $portfolio->members()->attach($owner->id, ['role'=>'owner']);

        Sanctum::actingAs($outsider);
        $this->postJson("/api/portfolios/{$portfolio->id}/assistant/authorize", [])->assertForbidden();
    }

    public function test_viewer_can_authorize_read_only_assistant(): void
    {
        $owner = User::create(['name'=>'Owner', 'email'=>'owner@assessment.test', 'password'=>'secret']);
        $viewer = User::create(['name'=>'Viewer', 'email'=>'viewer@assessment.test', 'password'=>'secret']);
        $portfolio = Portfolio::create(['name'=>'Private', 'base_currency'=>'USD', 'created_by'=>$owner->id]);
        $portfolio->members()->attach($viewer->id, ['role'=>'viewer']);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/portfolios/{$portfolio->id}/assistant/authorize", [])->assertNoContent();
    }

    public function test_unauthenticated_assistant_permission_check_is_rejected(): void
    {
        $this->postJson('/api/portfolios/999/assistant/authorize', [])->assertUnauthorized();
    }
}
