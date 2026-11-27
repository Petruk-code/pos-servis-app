<?php

namespace App\Notifications;

use App\Models\ServiceTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ServiceTicketStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ServiceTicket $ticket,
        public string $fromStatus,
        public string $toStatus,
        public ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'customer_name' => $this->ticket->customer_name,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'note' => $this->note,
            'url' => route('services.show', $this->ticket),
        ];
    }
}
