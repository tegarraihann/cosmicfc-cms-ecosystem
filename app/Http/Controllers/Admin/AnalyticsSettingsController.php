<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSetting;
use App\Services\GoogleAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnalyticsSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'measurement_id' => ['nullable', 'required_if:enabled,true', 'regex:/^G-[A-Z0-9]+$/', 'max:30'],
            'property_id' => ['nullable', 'required_if:enabled,true', 'regex:/^[0-9]+$/', 'max:30'],
            'cache_minutes' => ['required', 'integer', 'between:15,120'],
            'service_account_file' => ['nullable', 'file', 'mimetypes:application/json,text/plain', 'max:512'],
        ], [
            'measurement_id.regex' => 'Measurement ID harus menggunakan format G-XXXXXXXXXX.',
            'property_id.regex' => 'Property ID hanya boleh berisi angka.',
            'service_account_file.mimetypes' => 'Kredensial harus berupa file JSON.',
        ]);

        $setting = AnalyticsSetting::current();
        $credentials = $setting->service_account;
        if ($request->hasFile('service_account_file')) {
            $credentials = json_decode($request->file('service_account_file')->get(), true);
            if (! is_array($credentials) || ($credentials['type'] ?? null) !== 'service_account' || blank($credentials['client_email'] ?? null) || blank($credentials['private_key'] ?? null)) {
                return back()->withErrors(['service_account_file' => 'File bukan kredensial Service Account Google yang valid.']);
            }
        }
        if ($data['enabled'] && blank($credentials)) {
            return back()->withErrors(['service_account_file' => 'Unggah kredensial Service Account sebelum mengaktifkan GA4.']);
        }

        $setting->fill([
            'enabled' => $data['enabled'], 'measurement_id' => $data['measurement_id'],
            'property_id' => $data['property_id'], 'cache_minutes' => $data['cache_minutes'],
            'service_account' => $credentials, 'connection_status' => 'untested',
            'last_error' => null, 'updated_by' => $request->user()->id,
        ])->save();
        Cache::forget('ga4:summary:'.$setting->id);

        return back()->with('success', 'Konfigurasi Google Analytics 4 berhasil disimpan. Silakan uji koneksi.');
    }

    public function test(GoogleAnalyticsService $analytics): RedirectResponse
    {
        $setting = AnalyticsSetting::current();
        try {
            $analytics->testConnection($setting);
            $setting->forceFill(['connection_status' => 'connected', 'last_error' => null, 'last_tested_at' => now()])->save();

            return back()->with('success', 'Koneksi GA4 berhasil. Properti dapat dibaca oleh sistem.');
        } catch (\Throwable $exception) {
            report($exception);
            $setting->forceFill(['connection_status' => 'failed', 'last_error' => 'Koneksi gagal. Pastikan Property ID benar dan Service Account sudah diberi akses Viewer.', 'last_tested_at' => now()])->save();

            return back()->withErrors(['analytics_connection' => $setting->last_error]);
        }
    }

    public function destroyCredentials(): RedirectResponse
    {
        $setting = AnalyticsSetting::current();
        $setting->forceFill(['enabled' => false, 'service_account' => null, 'connection_status' => 'unconfigured', 'last_error' => null])->save();
        Cache::forget('ga4:summary:'.$setting->id);

        return back()->with('success', 'Kredensial GA4 telah dihapus dan pelacakan dinonaktifkan.');
    }
}
