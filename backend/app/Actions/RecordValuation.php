<?php
namespace App\Actions;
use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RecordValuation
{
    public function execute(Portfolio $portfolio, Asset $asset, User $actor, string $unitPrice): void
    {
        DB::transaction(function () use ($portfolio,$asset,$actor,$unitPrice): void {
            $locked=Asset::where('portfolio_id',$portfolio->id)->lockForUpdate()->findOrFail($asset->id);
            DB::table('asset_valuations')->insert([
                'portfolio_id'=>$portfolio->id,'asset_id'=>$locked->id,'recorded_by'=>$actor->id,
                'unit_price'=>$unitPrice,'valued_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
            ]);
            DB::table('assets')->where('id',$locked->id)->where('portfolio_id',$portfolio->id)
                ->update(['unit_price'=>$unitPrice,'valued_at'=>now(),'updated_at'=>now()]);
        },3);
    }
}
