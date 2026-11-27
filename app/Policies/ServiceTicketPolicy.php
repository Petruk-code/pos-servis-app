<?php

namespace App\Policies;

use App\Models\ServiceTicket;
use App\Models\User;

class ServiceTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['owner', 'kasir', 'teknisi'], true);
    }

    public function view(User $user, ServiceTicket $ticket): bool
    {
        if (in_array($user->role?->name, ['owner', 'kasir'], true)) {
            return true;
        }

        return $user->role?->name === 'teknisi'
            && ($ticket->technician_id === null || $ticket->technician_id === $user->id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['owner', 'kasir', 'teknisi'], true);
    }

    public function update(User $user, ServiceTicket $ticket): bool
    {
        if (in_array($user->role?->name, ['owner', 'kasir'], true)) {
            return true;
        }

        return $user->role?->name === 'teknisi'
            && ($ticket->technician_id === null || $ticket->technician_id === $user->id);
    }

    public function manageParts(User $user, ServiceTicket $ticket): bool
    {
        return $this->update($user, $ticket) && $ticket->transaction_id === null;
    }

    public function pay(User $user, ServiceTicket $ticket): bool
    {
        return in_array($user->role?->name, ['owner', 'kasir'], true) && $ticket->transaction_id === null;
    }

    public function print(User $user, ServiceTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
