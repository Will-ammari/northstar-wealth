<?php
namespace App\Policies;
use App\Models\Portfolio;
use App\Models\User;

class PortfolioPolicy
{
    public function view(User $user, Portfolio $portfolio): bool
    {
        return $portfolio->members()->where('users.id',$user->id)->exists();
    }
    public function mutate(User $user, Portfolio $portfolio): bool
    {
        return $portfolio->members()->where('users.id',$user->id)
            ->wherePivotIn('role',['owner','editor'])->exists();
    }
}
