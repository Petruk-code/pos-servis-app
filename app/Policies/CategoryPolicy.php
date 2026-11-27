<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool { return $user->role?->name === 'owner'; }
    public function view(User $user, Category $category): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->role?->name === 'owner'; }
    public function update(User $user, Category $category): bool { return $user->role?->name === 'owner'; }
    public function delete(User $user, Category $category): bool { return $user->role?->name === 'owner'; }
}