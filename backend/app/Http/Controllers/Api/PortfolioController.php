<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Queries\PortfolioDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PortfolioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $portfolios=$request->user()->portfolios()->select('portfolios.id','portfolios.name','portfolios.base_currency')
            ->orderBy('portfolios.id')->get()->map(fn ($p)=>[
                'id'=>$p->id,'name'=>$p->name,'base_currency'=>$p->base_currency,'role'=>$p->pivot->role,
            ]);
        return response()->json(['data'=>$portfolios]);
    }
    public function show(Portfolio $portfolio, PortfolioDashboard $dashboard): JsonResponse
    {
        Gate::authorize('view',$portfolio);
        return response()->json(['data'=>$dashboard->execute($portfolio)]);
    }
}
