<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsSuperAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SuperAdminRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_has_admin_and_superadmin_access(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->isSuperAdmin());
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_regular_admin_is_not_a_superadmin(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_superadmin_middleware_rejects_other_roles(): void
    {
        $request = Request::create('/superadmin-test');
        $request->setUserResolver(fn () => User::factory()->admin()->create());

        $this->expectException(HttpException::class);
        app(EnsureUserIsSuperAdmin::class)->handle($request, fn () => new Response('OK'));
    }
}
