<?php

namespace App\Console\Commands;

use App\Domain\Enums\TicketStatus;
use App\Jobs\SendTicketToWebservice;
use App\Models\Ticket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-unsent-tickets-to-webservice-command')]
#[Description('Command description')]
class SendUnsentTicketsToWebserviceCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Ticket::query()
            ->whereIn('status', [TicketStatus::ApprovedL2, TicketStatus::DeliveryFailed])
            ->each(function (Ticket $ticket) {
                SendTicketToWebservice::dispatch($ticket);
            });

        return self::SUCCESS;
    }
}
