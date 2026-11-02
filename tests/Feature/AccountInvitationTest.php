<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invited_user_can_verify_email_and_choose_password(): void
    {
        Event::fake([Verified::class]);
        $token = 'valid-invitation-token';
        $user = User::factory()->unverified()->create([
            'password' => str()->random(64),
            'invitation_token' => hash('sha256', $token),
            'invitation_expires_at' => now()->addHour(),
        ]);
        $url = URL::temporarySignedRoute('invitation.accept', now()->addHour(), [
            'user' => $user->id,
            'token' => $token,
        ]);

        $this->get($url)->assertOk();
        $this->post($url, [
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->invitation_token);
        $this->assertTrue(password_verify('PasswordBaru123!', $user->password));
        Event::assertDispatched(Verified::class);
    }

    public function test_expired_invitation_is_rejected(): void
    {
        $token = 'expired-token';
        $user = User::factory()->unverified()->create([
            'invitation_token' => hash('sha256', $token),
            'invitation_expires_at' => now()->subMinute(),
        ]);
        $url = URL::temporarySignedRoute('invitation.accept', now()->addHour(), [
            'user' => $user->id,
            'token' => $token,
        ]);

        $this->get($url)->assertForbidden();
    }
}
