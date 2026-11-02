<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PendingEmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();
        Notification::fake();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'current_password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertNotSame('test@example.com', $user->email);
        $this->assertSame('test@example.com', $user->pending_email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertSentOnDemand(PendingEmailVerificationNotification::class);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_pending_email_becomes_login_email_after_verification(): void
    {
        $token = 'email-change-token';
        $user = User::factory()->create([
            'pending_email' => 'new-email@example.com',
            'email_change_token' => hash('sha256', $token),
            'email_change_expires_at' => now()->addHour(),
            'must_change_email' => true,
        ]);
        $url = URL::temporarySignedRoute('email-change.verify', now()->addHour(), [
            'user' => $user->id,
            'token' => $token,
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('new-email@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertFalse($user->must_change_email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_users_cannot_delete_their_own_account_from_profile(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->delete('/profile')
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
