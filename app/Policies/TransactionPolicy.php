<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['owner', 'kasir'], true);
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->role?->name === 'owner'
            || ($user->role?->name === 'kasir' && $transaction->user_id === $user->id);
    }

    public function requestCancellation(User $user, Transaction $transaction): bool
    {
        return $user->role?->name === 'kasir'
            && $transaction->user_id === $user->id
            && $transaction->status === 'completed';
    }

    public function cancel(User $user, Transaction $transaction): bool
    {
        return $user->role?->name === 'owner' && $transaction->status === 'completed';
    }
}
