<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramNotificationService
{
    public function configured(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.chat_id'));
    }

    public function send(string $message): void
    {
        Http::asForm()
            ->timeout(10)
            ->retry(2, 250)
            ->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
                'chat_id' => config('services.telegram.chat_id'),
                'text' => $message,
                'disable_web_page_preview' => true,
            ])
            ->throw();
    }
}
