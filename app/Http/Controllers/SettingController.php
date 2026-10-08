<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public const COLOR_PALETTES = [
        [
            'id' => 'indigo',
            'name' => 'Indigo Prime',
            'hex' => '#6366f1',
            'class' => 'from-indigo-600 to-blue-600',
            'badgeClass' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border-indigo-200/50 dark:border-indigo-800/60',
        ],
        [
            'id' => 'blue',
            'name' => 'Ocean Blue',
            'hex' => '#2563eb',
            'class' => 'from-blue-600 to-sky-500',
            'badgeClass' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border-blue-200/50 dark:border-blue-800/60',
        ],
        [
            'id' => 'emerald',
            'name' => 'Emerald Green',
            'hex' => '#059669',
            'class' => 'from-emerald-600 to-teal-500',
            'badgeClass' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200/50 dark:border-emerald-800/60',
        ],
        [
            'id' => 'violet',
            'name' => 'Royal Violet',
            'hex' => '#7c3aed',
            'class' => 'from-violet-600 to-purple-500',
            'badgeClass' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300 border-purple-200/50 dark:border-purple-800/60',
        ],
        [
            'id' => 'amber',
            'name' => 'Warm Amber',
            'hex' => '#d97706',
            'class' => 'from-amber-500 to-orange-500',
            'badgeClass' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border-amber-200/50 dark:border-amber-800/60',
        ],
        [
            'id' => 'rose',
            'name' => 'Rose Crimson',
            'hex' => '#e11d48',
            'class' => 'from-rose-600 to-pink-500',
            'badgeClass' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border-rose-200/50 dark:border-rose-800/60',
        ],
        [
            'id' => 'cyan',
            'name' => 'Teal Cyan',
            'hex' => '#0891b2',
            'class' => 'from-cyan-600 to-blue-500',
            'badgeClass' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/70 dark:text-cyan-300 border-cyan-200/50 dark:border-cyan-800/60',
        ],
        [
            'id' => 'slate',
            'name' => 'Modern Slate',
            'hex' => '#475569',
            'class' => 'from-slate-700 to-zinc-600',
            'badgeClass' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        ],
    ];

    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Display the system settings and branding page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user->can('settings.view') && ! $user->can('settings.manage') && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized to view system settings.');
        }

        $branding = $this->settingService->getBranding();

        return Inertia::render('Settings/Index', [
            'branding' => $branding,
            'colorPalettes' => self::COLOR_PALETTES,
            'canManage' => $user->can('settings.manage') || $user->isSuperAdmin(),
        ]);
    }

    /**
     * Update branding, color, and upload logo.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('settings.manage') && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized to modify system settings.');
        }

        $validColorIds = array_column(self::COLOR_PALETTES, 'id');

        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:100'],
            'theme_color' => ['required', 'string', 'in:'.implode(',', $validColorIds)],
            'support_email' => ['nullable', 'email', 'max:100'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'telegram_support_username' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ]);

        $this->settingService->set('brand_name', $validated['brand_name']);
        $this->settingService->set('theme_color', $validated['theme_color']);

        if (array_key_exists('support_email', $validated)) {
            $this->settingService->set('support_email', $validated['support_email']);
        }
        if (array_key_exists('support_phone', $validated)) {
            $this->settingService->set('support_phone', $validated['support_phone']);
        }
        if (array_key_exists('address', $validated)) {
            $this->settingService->set('address', $validated['address']);
        }
        if (array_key_exists('telegram_support_username', $validated)) {
            $this->settingService->set('telegram_support_username', ltrim(trim((string) $validated['telegram_support_username']), '@'));
        }

        if ($request->hasFile('logo')) {
            $this->settingService->uploadLogo($request->file('logo'));
        }

        return redirect()->route('settings.index')->with('success', 'System branding settings successfully updated.');
    }

    /**
     * Delete the uploaded custom system logo.
     */
    public function deleteLogo(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('settings.manage') && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized to modify system settings.');
        }

        $this->settingService->deleteLogo();

        return redirect()->route('settings.index')->with('success', 'Custom logo removed. Default brand mark restored.');
    }
}
