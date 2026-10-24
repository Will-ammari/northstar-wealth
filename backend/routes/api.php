<?php
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AssistantAuthorizationController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ValuationController;
use Illuminate\Support\Facades\Route;

Route::post('/login',[AuthController::class,'login'])->middleware('throttle:5,1');
Route::middleware(['auth:sanctum','throttle:120,1'])->group(function (): void {
    Route::get('/me',[AuthController::class,'me']);
    Route::post('/logout',[AuthController::class,'logout']);
    Route::get('/portfolios',[PortfolioController::class,'index']);
    Route::get('/portfolios/{portfolio}',[PortfolioController::class,'show']);
    Route::post('/portfolios/{portfolio}/assistant/authorize', AssistantAuthorizationController::class)->middleware('throttle:8,1');
    Route::post('/portfolios/{portfolio}/assets',[AssetController::class,'store']);
    Route::post('/portfolios/{portfolio}/assets/{asset}/valuation',[ValuationController::class,'store']);
    Route::post('/portfolios/{portfolio}/assets/{asset}/transactions',[TransactionController::class,'store']);
});
