<?php
namespace App\Http\Controllers\Api;
use App\Actions\RecordTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Asset;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class TransactionController extends Controller
{
    public function store(StoreTransactionRequest $request, Portfolio $portfolio, Asset $asset, RecordTransaction $action): JsonResponse
    {
        abort_unless($asset->portfolio_id === $portfolio->id,404);
        Gate::authorize('mutate',$portfolio);
        if ($asset->type === 'cash' && !preg_match('/^0*1(?:\.0{1,8})?$/',(string)$request->validated('unit_price'))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['unit_price'=>'USD cash transactions require a unit value of 1.']);
        }
        $transaction=$action->execute($portfolio,$asset,$request->user(),$request->validated());
        return response()->json(['data'=>$transaction->only(['id','asset_id','type','quantity','unit_price','executed_at'])],201);
    }
}
