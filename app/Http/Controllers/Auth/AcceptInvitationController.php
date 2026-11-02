<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInvitationController extends Controller
{
    public function create(Request $request, User $user, string $token): Response
    {
        $this->ensureValidInvitation($user, $token);

        return Inertia::render('Auth/AcceptInvitation', [
            'name' => $user->name,
            'email' => $user->email,
            'submitUrl' => $request->fullUrl(),
        ]);
    }

    public function store(Request $request, User $user, string $token): RedirectResponse
    {
        $this->ensureValidInvitation($user, $token);
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => $data['password'],
            'email_verified_at' => now(),
            'invitation_token' => null,
            'invitation_expires_at' => null,
        ])->save();
        event(new Verified($user));

        return redirect()->route('login')->with('status', $user->is_active
            ? 'Akun berhasil diaktifkan. Silakan masuk menggunakan password yang baru dibuat.'
            : 'Email berhasil diverifikasi. Akun Anda masih dinonaktifkan oleh administrator.');
    }

    private function ensureValidInvitation(User $user, string $token): void
    {
        abort_unless(
            filled($user->invitation_token)
            && $user->invitation_expires_at?->isFuture()
            && hash_equals($user->invitation_token, hash('sha256', $token)),
            403,
            'Undangan tidak valid atau sudah kedaluwarsa.',
        );
    }
}
