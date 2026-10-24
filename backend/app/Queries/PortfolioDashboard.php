<?php
namespace App\Queries;
use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;

final class PortfolioDashboard
{
    public function execute(Portfolio $portfolio): array
    {
        $base = DB::table('assets')->where('portfolio_id',$portfolio->id);
        $summary = (clone $base)->selectRaw('COALESCE(SUM(quantity * unit_price),0) AS total_value, COUNT(*) AS asset_count')->first();
        $allocation = (clone $base)->select('type')->selectRaw('COALESCE(SUM(quantity * unit_price),0) AS value')
            ->groupBy('type')->orderBy('type')->get();
        $assets = (clone $base)->select(['id','type','symbol','name','quantity','unit_price','valued_at'])
            ->selectRaw('(quantity * unit_price) AS market_value')->orderBy('id')->limit(100)->get();
        $recent = DB::table('portfolio_transactions as t')->join('assets as a','a.id','=','t.asset_id')
            ->where('t.portfolio_id',$portfolio->id)->orderByDesc('t.executed_at')->orderByDesc('t.id')
            ->limit(10)->get(['t.id','t.type','t.quantity','t.unit_price','t.executed_at','a.name as asset_name']);
        return [
            'portfolio'=>['id'=>$portfolio->id,'name'=>$portfolio->name,'base_currency'=>$portfolio->base_currency],
            'total_value'=>(string)$summary->total_value,
            'asset_count'=>(int)$summary->asset_count,
            'allocation'=>$allocation,
            'assets'=>$assets,
            'recent_transactions'=>$recent,
        ];
    }
}
