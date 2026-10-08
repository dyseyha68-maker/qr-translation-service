<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    public const DEFAULT_BRAND_NAME = 'DIN & CO';
    public const DEFAULT_THEME_COLOR = 'indigo';

    /**
     * Get a setting by key, with optional fallback default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = AppSetting::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Store or update a setting.
     */
    public function set(string $key, mixed $value): void
    {
        AppSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value !== null ? (string) $value : null]
        );
    }

    /**
     * Return branding payload for Inertia shared props.
     *
     * @return array{brandName: string, themeColor: string, systemLogo: ?string}
     */
    public function getBranding(): array
    {
        $logoPath = $this->get('system_logo');
        $systemLogoUrl = null;

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $systemLogoUrl = '/storage/'.ltrim($logoPath, '/');
        }

        return [
            'brandName' => (string) $this->get('brand_name', self::DEFAULT_BRAND_NAME),
            'themeColor' => (string) $this->get('theme_color', self::DEFAULT_THEME_COLOR),
            'systemLogo' => $systemLogoUrl,
            'telegramSupportUsername' => (string) $this->get('telegram_support_username', config('services.telegram.support_username', 'seyhady')),
            'supportEmail' => (string) $this->get('support_email', ''),
            'supportPhone' => (string) $this->get('support_phone', ''),
            'address' => (string) $this->get('address', ''),
        ];
    }

    /**
     * Upload and store the system logo.
     */
    public function uploadLogo(UploadedFile $file): string
    {
        $oldPath = $this->get('system_logo');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $file->store('branding', 'public');
        $this->set('system_logo', $path);

        return '/storage/'.ltrim($path, '/');
    }

    /**
     * Delete the system logo.
     */
    public function deleteLogo(): void
    {
        $oldPath = $this->get('system_logo');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->set('system_logo', null);
    }
}
