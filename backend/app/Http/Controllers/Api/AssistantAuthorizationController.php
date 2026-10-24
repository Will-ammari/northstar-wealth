<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class AssistantAuthorizationController extends Controller
{
    public function __invoke(Portfolio $portfolio): Response
    {
        Gate::authorize('view', $portfolio);
        // Separate low-rate route limits the number of model invocations per identity.
        // Every actual tool query re-checks access in PortfolioController::show.
        return response()->noContent();
    }
}
