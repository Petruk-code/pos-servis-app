<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    private function owner(User $user): bool { return $user->role?->name === 'owner'; }
    public function viewAny(User $user): bool { return $this->owner($user); }
    public function view(User $user, Supplier $supplier): bool { return $this->owner($user); }
    public function create(User $user): bool { return $this->owner($user); }
    public function update(User $user, Supplier $supplier): bool { return $this->owner($user); }
    public function delete(User $user, Supplier $supplier): bool { return $this->owner($user); }
}
