<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBusinessSettingRequest;
use App\Models\BusinessSetting;
use App\Services\BusinessSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Tampilkan formulir pengaturan profil usaha, parameter operasional, dan cetak nota.
     */
    public function index(): View
    {
        $settings = BusinessSetting::allAsArray();
        $logoPath = $settings[BusinessSetting::KEY_BUSINESS_LOGO_PATH] ?? null;
        $logoUrl = $logoPath ? asset('storage/' . $logoPath) : null;

        return view('admin.settings.index', compact('settings', 'logoUrl'));
    }

    /**
     * Simpan pembaruan pengaturan usaha, logo, dan parameter cetak nota.
     */
    public function update(
        UpdateBusinessSettingRequest $request,
        BusinessSettingService $settingService
    ): RedirectResponse {
        $settingService->updateSettings(
            data: $request->validated(),
            logoFile: $request->file('logo'),
            actor: $request->user()
        );

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Pengaturan usaha dan parameter cetak nota berhasil disimpan.');
    }
}
