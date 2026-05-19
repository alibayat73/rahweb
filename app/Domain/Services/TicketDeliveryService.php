<?php

namespace App\Domain\Services;

use App\Contracts\DeliveryClientInterface;
use App\Models\DeliveryAttempt;
use App\Models\Ticket;

class TicketDeliveryService
{
    public function __construct(
        protected DeliveryClientInterface $client,
        protected TicketWorkflowService $workflow,
    ) {}

    public function send(Ticket $ticket): DeliveryAttempt
    {
        $result = $this->client->send($ticket);

        $attempt = $ticket->deliveryAttempts()->create([
            'ticket_id' => $ticket->id,
            'status' => $result->success ? 'success' : 'failed',
            'response_code' => $result->statusCode,
            'response_message' => $result->message,
            'attempted_at' => now(),
        ]);

        if ($result->success) {
            $this->workflow->markAsDelivered($ticket);
        } else {
            $this->workflow->markAsDeliveryFailed($ticket);
        }

        return $attempt;
    }
}
