<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'invitation.accept',
            now()->addHours(24),
            ['user' => $notifiable->getKey(), 'token' => $this->token],
        );

        return (new MailMessage)
            ->subject('Aktivasi akun COSMIC CMS')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Anda diundang untuk mengakses COSMIC CMS.')
            ->line('Verifikasi alamat email dan tentukan password melalui tombol berikut.')
            ->action('Aktifkan Akun', $url)
            ->line('Tautan ini berlaku selama 24 jam. Abaikan email ini jika Anda tidak mengenali undangan tersebut.');
    }
}
