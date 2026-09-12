<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Facades\Setting as SettingStore;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The global settings screen — one tab per group (M11.8).
 */
final class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'groups' => Setting::query()
                ->orderBy('key')
                ->get()
                ->groupBy('group')
                ->sortBy(function ($settings, string $group): string {
                    $position = array_search($group, Settings::GROUP_ORDER, true);

                    // Known groups keep the order above; the rest follow, sorted
                    // by name, so a group added later still lands somewhere sane.
                    return $position === false ? "z{$group}" : chr(97 + $position);
                }),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        SettingStore::setMany($request->settingsToSave());

        return to_route('admin.settings.edit', ['tab' => $request->string('tab')->toString()])
            ->with('status', __('Pengaturan tersimpan.'));
    }
}
