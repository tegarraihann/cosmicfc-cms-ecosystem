<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VerifyPendingEmailController extends Controller
{
    public function __invoke(User $user, string $token): RedirectResponse
    {
        abort_unless(
            filled($user->pending_email)
            && filled($user->email_change_token)
            && $user->email_change_expires_at?->isFuture()
            && hash_equals($user->email_change_token, hash('sha256', $token)),
            403,
            'Tautan verifikasi tidak valid atau sudah kedaluwarsa.',
        );

        abort_if(User::where('email', $user->pending_email)->whereKeyNot($user->id)->exists(), 422, 'Email tersebut sudah digunakan akun lain.');

        $user->forceFill([
            'email' => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
            'email_change_token' => null,
            'email_change_expires_at' => null,
            'must_change_email' => false,
        ])->save();
        event(new Verified($user));

        return Auth::check() && Auth::id() === $user->id
            ? redirect()->route('profile.edit')->with('success', 'Email baru berhasil diverifikasi dan sekarang digunakan untuk login.')
            : redirect()->route('login')->with('status', 'Email baru berhasil diverifikasi. Silakan masuk menggunakan alamat email baru.');
    }
}
