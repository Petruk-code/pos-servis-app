<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['owner', 'gudang'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['owner', 'gudang'], true);
    }
}
