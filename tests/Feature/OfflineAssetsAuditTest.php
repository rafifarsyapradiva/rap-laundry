<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OfflineAssetsAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Audit Statis: Pastikan SELURUH file Blade di resources/views/ tidak memuat link ke CDN eksternal.
     */
    public function test_all_blade_views_are_100_percent_free_of_external_cdns_and_apis(): void
    {
        $viewsPath = resource_path('views');
        $this->assertDirectoryExists($viewsPath);

        $viewFiles = File::allFiles($viewsPath);
        $this->assertNotEmpty($viewFiles, 'Direktori resources/views/ tidak boleh kosong.');

        $disallowedPatterns = [
            '/(https?:)?\/\/fonts\.googleapis\.com/i' => 'Google Fonts CDN terdeteksi',
            '/(https?:)?\/\/fonts\.gstatic\.com/i' => 'Google Fonts GStatic terdeteksi',
            '/(https?:)?\/\/cdnjs\.cloudflare\.com/i' => 'CDNJS terdeteksi',
            '/(https?:)?\/\/cdn\.jsdelivr\.net/i' => 'JSDelivr CDN terdeteksi',
            '/(https?:)?\/\/unpkg\.com/i' => 'Unpkg CDN terdeteksi',
            '/(https?:)?\/\/stackpath\.bootstrapcdn\.com/i' => 'Bootstrap CDN terdeteksi',
            '/(https?:)?\/\/code\.jquery\.com/i' => 'JQuery CDN terdeteksi',
            '/https?:\/\/[a-z0-9\.\-]+\/[^\s"\'<>]+/i' => 'URL HTTP/HTTPS eksternal terdeteksi',
        ];

        $violations = [];

        foreach ($viewFiles as $file) {
            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $content = $file->getContents();

            // Abaikan skema URL lokal jika ada (misal http://localhost untuk link dokumentasi internal jika ada)
            // Namun cek semua pola eksternal
            foreach ($disallowedPatterns as $pattern => $description) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        // Abaikan namespace XML (seperti xmlns="http://www.w3.org/2000/svg")
                        if (str_contains($match, 'www.w3.org/2000/svg')) {
                            continue;
                        }
                        // Abaikan localhost / 127.0.0.1
                        if (str_contains($match, 'localhost') || str_contains($match, '127.0.0.1')) {
                            continue;
                        }

                        $violations[] = "[{$relativePath}] {$description}: {$match}";
                    }
                }
            }
        }

        $this->assertEmpty($violations, "Ditemukan referensi CDN/eksternal pada file Blade views:\n" . implode("\n", $violations));
    }

    /**
     * 2. Audit Statis: Pastikan SELURUH file CSS di public/css/ tidak mengimpor font/file eksternal.
     */
    public function test_all_public_css_files_are_strictly_offline(): void
    {
        $cssPath = public_path('css');
        if (! File::isDirectory($cssPath)) {
            $this->markTestSkipped('Direktori public/css belum dibuat.');
        }

        $cssFiles = File::allFiles($cssPath);
        $violations = [];

        foreach ($cssFiles as $file) {
            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $content = $file->getContents();

            if (preg_match('/@import\s+url\s*\(\s*["\']?https?:/i', $content, $match)) {
                $violations[] = "[{$relativePath}] External CSS @import terdeteksi: {$match[0]}";
            }

            if (preg_match('/url\s*\(\s*["\']?https?:/i', $content, $match)) {
                $violations[] = "[{$relativePath}] External font/image URL terdeteksi: {$match[0]}";
            }
        }

        $this->assertEmpty($violations, "Ditemukan referensi eksternal pada file CSS:\n" . implode("\n", $violations));
    }

    /**
     * 3. Audit Dinamis: Layout utama dan halaman cetak nota hanya menyertakan aset lokal.
     */
    public function test_rendered_pages_and_receipts_only_link_to_local_assets(): void
    {
        $user = User::factory()->kasir()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        // Cek Dashboard
        $responseDashboard = $this->actingAs($user)->get(route('dashboard'));
        $responseDashboard->assertStatus(200);
        $dashboardContent = $responseDashboard->getContent();
        $this->assertStringNotContainsString('fonts.googleapis.com', $dashboardContent);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $dashboardContent);

        // Cek Cetak Nota Thermal
        $responseThermal = $this->actingAs($user)->get(route('orders.print', [$order, 'format' => 'thermal']));
        $responseThermal->assertStatus(200);
        $thermalContent = $responseThermal->getContent();
        $this->assertStringNotContainsString('fonts.googleapis.com', $thermalContent);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $thermalContent);

        // Cek Cetak Nota A4
        $responseA4 = $this->actingAs($user)->get(route('orders.print', [$order, 'format' => 'a4']));
        $responseA4->assertStatus(200);
        $a4Content = $responseA4->getContent();
        $this->assertStringNotContainsString('fonts.googleapis.com', $a4Content);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $a4Content);
    }
}
