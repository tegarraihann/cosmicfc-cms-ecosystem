<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchAdminPresence;
use App\Models\MatchNotificationLog;
use App\Models\User;
use App\Notifications\MatchResultReminderNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MatchReminderService
{
    public function __construct(private readonly TelegramNotificationService $telegram) {}

    public function sendDueReminders(?CarbonInterface $at = null): array
    {
        $at ??= now();
        $summary = ['telegram' => 0, 'email' => 0, 'skipped_active' => 0, 'failed' => 0];
        $leadMinutes = max(1, (int) config('services.match_reminders.lead_minutes', 15));

        GameMatch::query()
            ->with(['homeClub', 'awayClub'])
            ->where('status', 'live')
            ->whereNotNull('expected_end_at')
            ->where('expected_end_at', '>', $at)
            ->where('expected_end_at', '<=', $at->copy()->addMinutes($leadMinutes))
            ->orderBy('id')
            ->each(function (GameMatch $match) use (&$summary, $at): void {
                if ($this->hasActiveAdmin($match, $at)) {
                    $summary['skipped_active']++;

                    return;
                }

                $label = $match->homeClub?->display_name.' vs '.$match->awayClub?->display_name;
                $message = "Pengingat hasil pertandingan\n{$label}\nPerkiraan selesai: ".$match->expected_end_at?->timezone($match->timezone)->format('d M Y H.i').' WIB. Periksa kejadian dan skor di dashboard.';

                if ($this->telegram->configured() && $this->deliver($match, 'telegram', 'result_due_soon', (string) config('services.telegram.chat_id'), fn () => $this->telegram->send($message))) {
                    $summary['telegram']++;
                }

                $recipients = User::query()->where('is_active', true)->where(function ($query) {
                    $query->where('role', 'superadmin')->orWhereHas('roleRelation', fn ($role) => $role->where('slug', 'superadmin'));
                })->get();

                foreach ($recipients as $recipient) {
                    if ($this->deliver($match, 'email', 'result_due_soon', $recipient->email, fn () => Notification::send($recipient, new MatchResultReminderNotification($match)))) {
                        $summary['email']++;
                    }
                }
            });

        $summary['failed'] = MatchNotificationLog::query()
            ->where('created_at', '>=', $at->copy()->subMinutes(2))
            ->where('status', 'failed')
            ->count();

        return $summary;
    }

    private function hasActiveAdmin(GameMatch $match, CarbonInterface $at): bool
    {
        $ttl = max(1, (int) config('services.match_reminders.presence_ttl_minutes', 3));

        return MatchAdminPresence::query()
            ->where('match_id', $match->id)
            ->where('last_seen_at', '>=', $at->copy()->subMinutes($ttl))
            ->exists();
    }

    private function deliver(GameMatch $match, string $channel, string $type, ?string $recipient, callable $callback): bool
    {
        $key = hash('sha256', implode('|', [$match->id, $channel, $type, $recipient]));
        $log = MatchNotificationLog::firstOrCreate(['idempotency_key' => $key], [
            'match_id' => $match->id,
            'channel' => $channel,
            'notification_type' => $type,
            'recipient' => $recipient,
            'status' => 'pending',
        ]);

        if (! $log->wasRecentlyCreated || $log->status === 'sent') {
            return false;
        }

        try {
            $callback();
            $log->update(['status' => 'sent', 'sent_at' => now(), 'failed_at' => null, 'error_summary' => null]);

            return true;
        } catch (\Throwable $exception) {
            $log->update(['status' => 'failed', 'failed_at' => now(), 'error_summary' => 'Pengiriman gagal ('.$exception::class.').']);
            Log::warning('Pengingat pertandingan gagal dikirim.', ['match_id' => $match->id, 'channel' => $channel, 'exception' => $exception::class]);

            return false;
        }
    }
}
