<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PendingEmailVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $userId,
        private readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'email-change.verify',
            now()->addMinutes(60),
            ['user' => $this->userId, 'token' => $this->token],
        );

        return (new MailMessage)
            ->subject('Verifikasi email baru COSMIC CMS')
            ->greeting('Verifikasi alamat email baru')
            ->line('Alamat ini diminta sebagai email login baru untuk akun COSMIC CMS.')
            ->action('Verifikasi Email Baru', $url)
            ->line('Tautan berlaku selama 60 menit. Email login lama tetap aktif sampai proses ini selesai.');
    }
}
