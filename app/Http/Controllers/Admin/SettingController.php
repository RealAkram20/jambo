<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private array $fileFields = ['logo', 'favicon', 'preloader'];

    public function index()
    {
        return view('admin.settings.index');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name'         => ['required', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'logo'             => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'favicon'          => ['nullable', 'image', 'mimes:png,ico,x-icon,svg', 'max:512'],
            'preloader'        => ['nullable', 'mimes:gif,png,jpg,jpeg,webp,svg', 'max:2048'],
            'logo_url'         => ['nullable', 'string', 'max:1000'],
            'favicon_url'      => ['nullable', 'string', 'max:1000'],
            'preloader_url'    => ['nullable', 'string', 'max:1000'],
        ]);

        Setting::set('app_name', $data['app_name']);
        Setting::set('meta_description', $data['meta_description'] ?? '');

        foreach ($this->fileFields as $field) {
            if ($request->hasFile($field)) {
                $old = Setting::get($field);
                if ($old && str_starts_with($old, '/storage/branding/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $old));
                }
                $path = $request->file($field)->store('branding', 'public');
                Setting::set($field, Storage::url($path));
                continue;
            }

            $urlKey = $field . '_url';
            if ($request->filled($urlKey)) {
                $url = $this->normalizeMediaUrl($request->input($urlKey));
                $old = Setting::get($field);
                if ($old && $old !== $url && str_starts_with($old, '/storage/branding/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $old));
                }
                Setting::set($field, $url);
            }
        }

        Setting::flushCache();

        return redirect()->route('admin.settings.index')
            ->with('status', __('messages.setting_save') ?? 'Settings updated.');
    }

    /**
     * Accept absolute URLs or media paths copied from the File Manager.
     * Examples:
     *   https://site.test/storage/media/logos/x.png  → /storage/media/logos/x.png
     *   /storage/media/logos/x.png                   → unchanged
     *   media/logos/x.png                            → /storage/media/logos/x.png
     */
    private function normalizeMediaUrl(string $url): string
    {
        $url = trim($url);
        $appUrl = rtrim(config('app.url'), '/');
        if ($appUrl && str_starts_with($url, $appUrl)) {
            $url = substr($url, strlen($appUrl));
        }
        if (str_starts_with($url, 'media/') || str_starts_with($url, 'branding/')) {
            $url = '/storage/' . $url;
        }
        return $url;
    }
}
