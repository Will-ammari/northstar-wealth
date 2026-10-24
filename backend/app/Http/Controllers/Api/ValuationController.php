<?php
namespace App\Http\Controllers\Api;
use App\Actions\RecordValuation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreValuationRequest;
use App\Models\Asset;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
final class ValuationController extends Controller
{
    public function store(StoreValuationRequest $request,Portfolio $portfolio,Asset $asset,RecordValuation $action): JsonResponse
    {
        abort_unless($asset->portfolio_id === $portfolio->id,404);
        Gate::authorize('mutate',$portfolio);
        if ($asset->type === 'cash' && !preg_match('/^0*1(?:\.0{1,8})?$/',(string)$request->validated('unit_price'))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['unit_price'=>'USD cash must have a unit value of 1.']);
        }
        $action->execute($portfolio,$asset,$request->user(),(string)$request->validated('unit_price'));
        return response()->json(['ok'=>true],201);
    }
}
