<?php

namespace App\Policies;

use App\Models\CancellationRequest;
use App\Models\User;

class CancellationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role?->name === 'owner';
    }

    public function review(User $user, CancellationRequest $request): bool
    {
        return $user->role?->name === 'owner' && $request->status === 'pending';
    }
}
