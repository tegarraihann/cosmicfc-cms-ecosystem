<?php

namespace App\Notifications;

use App\Models\GameMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MatchResultReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly GameMatch $match) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->match->homeClub?->display_name.' vs '.$this->match->awayClub?->display_name;

        return (new MailMessage)
            ->subject('Pengingat hasil pertandingan COSMIC')
            ->greeting('Halo '.$notifiable->name.',')
            ->line("Pertandingan {$label} mendekati waktu selesai.")
            ->line('Pastikan kejadian, skor, dan hasil akhir sudah diperiksa di dashboard.')
            ->action('Kelola Pertandingan', route('admin.matches.index'))
            ->line('Pesan ini dikirim otomatis sebagai pengingat operasional.');
    }
}
