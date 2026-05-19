<?php

namespace App\Domain\Services;

use App\Domain\Enums\Decision;
use App\Domain\Enums\TicketStatus;
use App\Jobs\SendTicketToWebservice;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketApprovedNotification;
use App\Notifications\TicketRejectedNotification;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class TicketWorkflowService
{
    public function submit(User $user, string $title, string $description, UploadedFile $attachment): Ticket
    {
        return Ticket::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'description' => $description,
            'attachment_path' => $attachment->store('tickets', 'public'),
            'status' => TicketStatus::Pending,
        ]);
    }

    public function approve(Ticket $ticket, string $approvalNote): Ticket
    {
        $admin = auth()->user();

        if ($admin->isAdminL1()) {
            $ticket = $this->approveLevel1($ticket, $approvalNote);
        } elseif ($admin->isAdminL2()) {
            $ticket = $this->approveLevel2($ticket, $approvalNote);
            SendTicketToWebservice::dispatch($ticket);
        }

        $ticket->user->notify(new TicketApprovedNotification($ticket));

        return $ticket;
    }

    private function approveLevel1(Ticket $ticket, string $note): Ticket
    {
        if (! $ticket->status->canBeApprovedByLevel1()) {
            throw new InvalidArgumentException('Ticket cannot be approved by Level 1 admin.');
        }

        $ticket->update(['status' => TicketStatus::ApprovedL1]);

        $ticket->decisions()->create([
            'admin_id' => auth()->id(),
            'decision' => Decision::Approved,
            'note' => $note,
            'level' => 1,
        ]);

        return $ticket;
    }

    private function approveLevel2(Ticket $ticket, string $note): Ticket
    {
        if (! $ticket->status->canBeApprovedByLevel2()) {
            throw new InvalidArgumentException('Ticket cannot be approved by Level 2 admin.');
        }

        $ticket->update(['status' => TicketStatus::ApprovedL2]);

        $ticket->decisions()->create([
            'admin_id' => auth()->id(),
            'decision' => Decision::Approved,
            'note' => $note,
            'level' => 2,
        ]);

        return $ticket;
    }

    public function reject(Ticket $ticket, string $rejectionNote): Ticket
    {
        $admin = auth()->user();

        if ($admin->isAdminL1()) {
            $ticket = $this->rejectLevel1($ticket, $rejectionNote);
        } elseif ($admin->isAdminL2()) {
            $ticket = $this->rejectLevel2($ticket, $rejectionNote);
        }

        $ticket->user->notify(new TicketRejectedNotification($ticket));

        return $ticket;
    }

    private function rejectLevel1(Ticket $ticket, string $note): Ticket
    {
        if (! $ticket->status->canBeApprovedByLevel1()) {
            throw new InvalidArgumentException('Ticket cannot be rejected by Level 1 admin.');
        }

        $ticket->update(['status' => TicketStatus::RejectedL1]);

        $ticket->decisions()->create([
            'admin_id' => auth()->id(),
            'decision' => Decision::Rejected,
            'note' => $note,
            'level' => 1,
        ]);

        return $ticket;
    }

    private function rejectLevel2(Ticket $ticket, string $note): Ticket
    {
        if (! $ticket->status->canBeApprovedByLevel2()) {
            throw new InvalidArgumentException('Ticket cannot be rejected by Level 2 admin.');
        }

        $ticket->update(['status' => TicketStatus::RejectedL2]);

        $ticket->decisions()->create([
            'admin_id' => auth()->id(),
            'decision' => Decision::Rejected,
            'note' => $note,
            'level' => 2,
        ]);

        return $ticket;
    }

    public function markAsDelivered(Ticket $ticket): Ticket
    {
        if ($ticket->status !== TicketStatus::ApprovedL2) {
            throw new InvalidArgumentException('Ticket must be approved by Level 2 to be marked as delivered.');
        }

        $ticket->status = TicketStatus::Delivered;
        $ticket->save();

        return $ticket;
    }

    public function markAsDeliveryFailed(Ticket $ticket): Ticket
    {
        if ($ticket->status !== TicketStatus::ApprovedL2) {
            throw new InvalidArgumentException('Ticket must be approved by Level 2 to be marked as delivery failed.');
        }

        $ticket->status = TicketStatus::DeliveryFailed;
        $ticket->save();

        return $ticket;
    }
}
