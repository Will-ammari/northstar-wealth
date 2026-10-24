<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PortfolioTransaction extends Model
{
    protected $table = 'portfolio_transactions';
    protected $fillable = ['portfolio_id','asset_id','actor_id','type','quantity','unit_price','idempotency_key','executed_at'];
    protected function casts(): array { return ['executed_at'=>'immutable_datetime']; }
    public function asset() { return $this->belongsTo(Asset::class); }
}
