<?php

namespace App\Policies;

use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool { return $user->role?->name === 'owner'; }
    public function view(User $user, User $employee): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->role?->name === 'owner'; }
    public function update(User $user, User $employee): bool { return $user->role?->name === 'owner'; }
    public function delete(User $user, User $employee): bool { return $user->role?->name === 'owner'; }
}