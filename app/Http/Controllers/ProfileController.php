<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Notifications\PendingEmailVerificationNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'pendingEmail' => $request->user()->pending_email,
            'pendingEmailExpiresAt' => $request->user()->email_change_expires_at?->toIso8601String(),
            'mustChangeEmail' => $request->user()->must_change_email,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $user->name = $data['name'];

        if (strtolower($data['email']) === strtolower($user->email)) {
            $user->save();

            return Redirect::route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
        }

        try {
            DB::transaction(function () use ($user, $data): void {
                $token = Str::random(64);
                $user->forceFill([
                    'pending_email' => strtolower($data['email']),
                    'email_change_token' => hash('sha256', $token),
                    'email_change_expires_at' => now()->addHour(),
                ])->save();

                Notification::route('mail', $user->pending_email)
                    ->notify(new PendingEmailVerificationNotification($user->id, $token));
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => 'Tautan verifikasi gagal dikirim. Periksa konfigurasi email lalu coba kembali.']);
        }

        return Redirect::route('profile.edit')->with('success', 'Tautan verifikasi telah dikirim ke email baru. Email login lama masih tetap aktif.');
    }

    public function resendPendingEmail(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if(blank($user->pending_email), 422, 'Tidak ada perubahan email yang menunggu verifikasi.');

        $token = Str::random(64);
        try {
            DB::transaction(function () use ($user, $token): void {
                $user->forceFill([
                    'email_change_token' => hash('sha256', $token),
                    'email_change_expires_at' => now()->addHour(),
                ])->save();
                Notification::route('mail', $user->pending_email)
                    ->notify(new PendingEmailVerificationNotification($user->id, $token));
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => 'Tautan verifikasi gagal dikirim ulang. Periksa konfigurasi email lalu coba kembali.']);
        }

        return back()->with('success', 'Tautan verifikasi email baru berhasil dikirim ulang.');
    }

    public function cancelPendingEmail(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'pending_email' => null,
            'email_change_token' => null,
            'email_change_expires_at' => null,
        ])->save();

        return back()->with('success', 'Perubahan email dibatalkan.');
    }
}
