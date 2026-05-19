<?php

namespace App\Policies;

use App\Domain\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $ticket->user_id === $user->id;
    }

    public function approve(User $user, Ticket $ticket): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        if ($user->isAdminL1()) {
            return $ticket->status === TicketStatus::Pending;
        }

        if ($user->isAdminL2()) {
            return $ticket->status === TicketStatus::ApprovedL1;
        }

        return false;
    }

    public function bulkApprove(User $user): bool
    {
        return $user->isAdmin();
    }
}
