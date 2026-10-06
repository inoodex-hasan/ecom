<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $settings = Setting::all()->keyBy('key');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => 'nullable|array',
            'settings.*' => 'nullable|string',
            'store_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:3072',
            'store_favicon_file' => 'nullable|file|mimes:ico,png,svg,webp,jpg|max:1024',
            'remove_logo' => 'nullable|boolean',
            'remove_favicon' => 'nullable|boolean',
        ]);

        if ($request->has('settings') && is_array($request->input('settings'))) {
            $upsertData = [];
            foreach ($request->input('settings') as $key => $value) {
                $upsertData[] = [
                    'key' => (string) $key,
                    'value' => (string) $value,
                ];
            }
            if (! empty($upsertData)) {
                Setting::upsert($upsertData, ['key'], ['value']);
            }
        }

        // Handle Store Logo Upload / Removal
        if ($request->hasFile('store_logo_file')) {
            $oldLogo = Setting::where('key', 'store_logo')->value('value');
            if ($oldLogo && str_starts_with($oldLogo, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLogo));
            }

            $path = $request->file('store_logo_file')->store('branding', 'public');
            Setting::updateOrCreate(
                ['key' => 'store_logo'],
                ['value' => '/storage/'.$path]
            );
        } elseif ($request->boolean('remove_logo')) {
            $oldLogo = Setting::where('key', 'store_logo')->value('value');
            if ($oldLogo && str_starts_with($oldLogo, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLogo));
            }
            Setting::where('key', 'store_logo')->delete();
        }

        // Handle Store Favicon Upload / Removal
        if ($request->hasFile('store_favicon_file')) {
            $oldFavicon = Setting::where('key', 'store_favicon')->value('value');
            if ($oldFavicon && str_starts_with($oldFavicon, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldFavicon));
            }

            $path = $request->file('store_favicon_file')->store('branding', 'public');
            Setting::updateOrCreate(
                ['key' => 'store_favicon'],
                ['value' => '/storage/'.$path]
            );
        } elseif ($request->boolean('remove_favicon')) {
            $oldFavicon = Setting::where('key', 'store_favicon')->value('value');
            if ($oldFavicon && str_starts_with($oldFavicon, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldFavicon));
            }
            Setting::where('key', 'store_favicon')->delete();
        }

        // Invalidate cached site settings
        Cache::forget('site_settings');

        return redirect()->back()->with('success', 'Store settings and brand assets updated successfully.');
    }
}
