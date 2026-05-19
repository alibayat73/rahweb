<?php

namespace App\Jobs;

use App\Domain\Services\TicketDeliveryService;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTicketToWebservice implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function handle(TicketDeliveryService $deliveryService): void
    {
        if ($this->ticket->status->isTerminal()) {
            return;
        }

        $attempt = $deliveryService->send($this->ticket);

        if ($attempt->status !== 'success') {
            throw new \Exception("Delivery failed: $attempt->response_message");
        }
    }
}
