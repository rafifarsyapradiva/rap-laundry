<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BusinessSettingService
{
    /**
     * Perbarui konfigurasi usaha, proses unggah/penghapusan logo, dan catat jejak audit aktivitas.
     *
     * @param  array<string, mixed>  $data
     * @param  UploadedFile|null     $logoFile
     * @param  User                  $actor
     * @return array<string, ?string>
     */
    public function updateSettings(array $data, ?UploadedFile $logoFile, User $actor): array
    {
        return DB::transaction(function () use ($data, $logoFile, $actor) {
            $currentSettings = BusinessSetting::allAsArray();
            $changedKeys = [];
            $oldValues = [];
            $newValues = [];

            // 1. Tangani Logo Usaha (Hapus atau Unggah Baru)
            $oldLogoPath = $currentSettings[BusinessSetting::KEY_BUSINESS_LOGO_PATH] ?? null;

            if (! empty($data['remove_logo'])) {
                if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                    Storage::disk('public')->delete($oldLogoPath);
                }
                BusinessSetting::set(BusinessSetting::KEY_BUSINESS_LOGO_PATH, null);

                if ($oldLogoPath !== null) {
                    $changedKeys[] = BusinessSetting::KEY_BUSINESS_LOGO_PATH;
                    $oldValues[BusinessSetting::KEY_BUSINESS_LOGO_PATH] = $oldLogoPath;
                    $newValues[BusinessSetting::KEY_BUSINESS_LOGO_PATH] = null;
                }
            } elseif ($logoFile instanceof UploadedFile) {
                // Hapus berkas logo lama jika ada
                if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                    Storage::disk('public')->delete($oldLogoPath);
                }

                // Simpan berkas logo baru ke direktori publik offline
                $newLogoPath = $logoFile->store('logos', 'public');
                BusinessSetting::set(BusinessSetting::KEY_BUSINESS_LOGO_PATH, $newLogoPath);

                $changedKeys[] = BusinessSetting::KEY_BUSINESS_LOGO_PATH;
                $oldValues[BusinessSetting::KEY_BUSINESS_LOGO_PATH] = $oldLogoPath;
                $newValues[BusinessSetting::KEY_BUSINESS_LOGO_PATH] = $newLogoPath;
            }

            // 2. Daftar Pemetaan Kunci Konfigurasi yang Dapat Diubah
            $settingMap = [
                BusinessSetting::KEY_BUSINESS_NAME => $data['business_name'] ?? null,
                BusinessSetting::KEY_BUSINESS_ADDRESS => $data['business_address'] ?? null,
                BusinessSetting::KEY_BUSINESS_PHONE => $data['business_phone'] ?? null,
                BusinessSetting::KEY_OPERATING_HOURS => $data['operating_hours'] ?? null,
                BusinessSetting::KEY_ORDER_NUMBER_PREFIX => $data['order_number_prefix'] ?? null,
                BusinessSetting::KEY_DEFAULT_ESTIMATED_HOURS => isset($data['default_estimated_hours']) ? (string) $data['default_estimated_hours'] : null,
                BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT => isset($data['cashier_max_discount_percent']) ? (string) $data['cashier_max_discount_percent'] : null,
                BusinessSetting::KEY_RECEIPT_FOOTER_NOTE => $data['receipt_footer_note'] ?? null,
                BusinessSetting::KEY_PRINT_FORMAT => $data['print_format'] ?? null,
            ];

            foreach ($settingMap as $key => $newValue) {
                $oldVal = $currentSettings[$key] ?? null;

                // Bandingkan apakah terjadi perubahan nilai
                if ((string) $oldVal !== (string) $newValue) {
                    BusinessSetting::set($key, $newValue);
                    $changedKeys[] = $key;
                    $oldValues[$key] = $oldVal;
                    $newValues[$key] = $newValue;
                }
            }

            // 3. Catat Jejak Audit Jika Terjadi Perubahan Konfigurasi
            if (! empty($changedKeys)) {
                ActivityLog::create([
                    'user_id' => $actor->id,
                    'action' => ActivityAction::SettingUpdated,
                    'subject_type' => BusinessSetting::class,
                    'subject_id' => null,
                    'description' => "Pengaturan usaha dan cetak nota diperbarui oleh {$actor->name}.",
                    'metadata' => [
                        'actor_username' => $actor->username,
                        'changed_keys' => $changedKeys,
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                    ],
                ]);
            }

            return BusinessSetting::allAsArray();
        });
    }
}
