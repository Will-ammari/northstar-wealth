<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Portfolio extends Model
{
    protected $fillable = ['name','base_currency','created_by'];
    public function assets() { return $this->hasMany(Asset::class); }
    public function members() { return $this->belongsToMany(User::class,'portfolio_memberships')->withPivot('role')->withTimestamps(); }
    public function transactions() { return $this->hasMany(PortfolioTransaction::class); }
}
