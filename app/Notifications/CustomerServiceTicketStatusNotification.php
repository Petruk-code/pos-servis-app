<?php

namespace App\Notifications;

use App\Models\ServiceTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerServiceTicketStatusNotification extends Notification
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
        return $this->ticket->customer_email ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'antre' => 'Antre',
            'dicek' => 'Sedang Dicek',
            'menunggu_sparepart' => 'Menunggu Sparepart',
            'selesai' => 'Selesai (Siap Diambil)',
            'diambil' => 'Sudah Diambil',
            'batal' => 'Dibatalkan',
        ];

        $toLabel = $statusLabels[$this->toStatus] ?? $this->toStatus;
        $ticket = $this->ticket;

        $mail = (new MailMessage)
            ->subject("Update Servis {$ticket->ticket_number}: {$toLabel}")
            ->greeting("Halo {$ticket->customer_name},")
            ->line("Status servis perangkat Anda telah diperbarui.")
            ->line("**No. Tiket:** {$ticket->ticket_number}")
            ->line("**Perangkat:** {$ticket->device_brand_model}")
            ->line("**Status:** {$toLabel}");

        if ($this->note) {
            $mail->line("**Catatan:** {$this->note}");
        }

        if ($this->toStatus === 'selesai') {
            $mail->line('Perangkat Anda sudah selesai dan dapat diambil. Silakan bawa tanda terima/nomor tiket saat pengambilan.');
        }

        return $mail->line('Terima kasih telah menggunakan layanan POS Servis.');
    }
}
