<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket)
    {
        $this->onQueue('ticket-notification');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket Approved - {$this->ticket->title}")
            ->line("Your ticket \"{$this->ticket->title}\" has been approved by Admin Level {$this->ticket->level}.")
            ->line("Status: {$this->ticket->status->label()}")
            ->action('View Ticket', url("/tickets/{$this->ticket->id}"))
            ->line('Thank you for using our ticketing system!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'level' => $this->ticket->level,
            'status' => $this->ticket->status->value,
            'message' => "Your ticket has been approved by Admin Level {$this->ticket->level}.",
        ];
    }
}
