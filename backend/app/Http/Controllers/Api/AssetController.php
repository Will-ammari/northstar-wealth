<?php
namespace App\Http\Controllers\Api;
use App\Actions\CreateAsset;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class AssetController extends Controller
{
    public function store(StoreAssetRequest $request, Portfolio $portfolio, CreateAsset $action): JsonResponse
    {
        Gate::authorize('mutate',$portfolio);
        $asset=$action->execute($portfolio,$request->user(),$request->validated());
        return response()->json(['data'=>$asset->only(['id','type','name','symbol','quantity','unit_price'])],201);
    }
}
