<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Asset extends Model
{
    protected $fillable = ['portfolio_id','type','symbol','name','quantity','unit_price','valued_at'];
    // No float casts: MySQL DECIMAL values must be serialized as strings.
    public function portfolio() { return $this->belongsTo(Portfolio::class); }
    public function transactions() { return $this->hasMany(PortfolioTransaction::class); }
}
