<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    private function owner(User $user): bool { return $user->role?->name === 'owner'; }
    public function viewAny(User $user): bool { return $this->owner($user); }
    public function view(User $user, PurchaseOrder $po): bool { return $this->owner($user); }
    public function create(User $user): bool { return $this->owner($user); }
    public function update(User $user, PurchaseOrder $po): bool { return $this->owner($user) && $po->status === 'draft'; }
    public function receive(User $user, PurchaseOrder $po): bool { return $this->owner($user) && $po->status === 'draft'; }
    public function cancel(User $user, PurchaseOrder $po): bool { return $this->owner($user) && $po->status === 'draft'; }
}
