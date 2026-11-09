<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class StatusPageTest extends TestCase
{
    public function test_browser_errors_use_the_cosmic_status_page(): void
    {
        foreach ([403, 404, 419, 429, 503] as $status) {
            Route::get("/_status-test/{$status}", fn () => abort($status));

            $this->get("/_status-test/{$status}")
                ->assertStatus($status)
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Errors/Status')
                    ->where('status', $status)
                    ->where('isAuthenticated', false)
                    ->where('canAccessDashboard', false));
        }
    }

    public function test_json_errors_keep_their_original_response_format(): void
    {
        Route::get('/_status-test/json', fn () => abort(403));

        $this->getJson('/_status-test/json')
            ->assertForbidden()
            ->assertJsonMissing(['component' => 'Errors/Status']);
    }

    public function test_production_server_error_uses_the_cosmic_status_page(): void
    {
        config()->set('app.debug', false);
        Route::get('/_status-test/server-error', fn () => throw new RuntimeException('Sensitive error'));

        $this->get('/_status-test/server-error')
            ->assertStatus(500)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Status')
                ->where('status', 500));
    }
}
