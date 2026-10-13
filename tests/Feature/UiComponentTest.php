<?php

namespace Tests\Feature;

use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UiComponentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);
    }

    /**
     * Test master layout and dashboard render successfully for cashier.
     */
    public function test_dashboard_renders_with_master_layout_for_cashier(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('LaundryDesk');
        $response->assertSee('Dashboard');
        $response->assertSee($this->cashier->name);
        $response->assertSee('Kasir');
        // Cashier should NOT see admin management section
        $response->assertDontSee('Manajemen Pemilik');
    }

    /**
     * Test master layout and dashboard render successfully for admin.
     */
    public function test_dashboard_renders_with_master_layout_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('LaundryDesk');
        $response->assertSee($this->admin->name);
        $response->assertSee('Admin');
        // Admin should see management section
        $response->assertSee('Manajemen Pemilik');
        $response->assertSee('Manajemen Pengguna');
        $response->assertSee('Pengaturan Usaha');
        $response->assertSee('Log Audit Aktivitas');
    }

    /**
     * Test stat-card component renders correctly.
     */
    public function test_stat_card_component_renders_correctly(): void
    {
        $html = Blade::render('<x-stat-card title="Total Pesanan" value="128" icon="orders" color="primary" hint="Pesanan hari ini" />');

        $this->assertStringContainsString('Total Pesanan', $html);
        $this->assertStringContainsString('128', $html);
        $this->assertStringContainsString('stat-card', $html);
        $this->assertStringContainsString('stat-icon-primary', $html);
        $this->assertStringContainsString('Pesanan hari ini', $html);
    }

    /**
     * Test badge-status component renders correctly for LaundryStatus and PaymentStatus.
     */
    public function test_badge_status_component_renders_enums_and_strings(): void
    {
        // Test with LaundryStatus enum
        $htmlDicuci = Blade::render('<x-badge-status :status="\App\Enums\LaundryStatus::Dicuci" />');
        $this->assertStringContainsString('badge-warning', $htmlDicuci);
        $this->assertStringContainsString('Dicuci', $htmlDicuci);

        $htmlSelesai = Blade::render('<x-badge-status :status="\App\Enums\LaundryStatus::Selesai" />');
        $this->assertStringContainsString('badge-success', $htmlSelesai);
        $this->assertStringContainsString('Selesai/Diambil', $htmlSelesai);

        // Test with PaymentStatus enum
        $htmlLunas = Blade::render('<x-badge-status :status="\App\Enums\PaymentStatus::Lunas" />');
        $this->assertStringContainsString('badge-success', $htmlLunas);
        $this->assertStringContainsString('Lunas', $htmlLunas);

        $htmlBelumBayar = Blade::render('<x-badge-status :status="\App\Enums\PaymentStatus::BelumDibayar" />');
        $this->assertStringContainsString('badge-danger', $htmlBelumBayar);
        $this->assertStringContainsString('Belum Dibayar', $htmlBelumBayar);

        // Test with string value
        $htmlString = Blade::render('<x-badge-status status="siap_diambil" />');
        $this->assertStringContainsString('badge-primary', $htmlString);
        $this->assertStringContainsString('Siap Diambil', $htmlString);
    }

    /**
     * Test table, button, and empty state components.
     */
    public function test_table_button_and_empty_state_components(): void
    {
        // Table
        $htmlTable = Blade::render('<x-table :headers="[\'Nama\', \'Nomor\']"><tr><td>John</td><td>01</td></tr></x-table>');
        $this->assertStringContainsString('table-glass', $htmlTable);
        $this->assertStringContainsString('<th>Nama</th>', $htmlTable);
        $this->assertStringContainsString('<td>John</td>', $htmlTable);

        // Button
        $htmlButton = Blade::render('<x-button variant="danger" size="sm" icon="trash">Hapus</x-button>');
        $this->assertStringContainsString('btn-danger', $htmlButton);
        $this->assertStringContainsString('btn-sm', $htmlButton);
        $this->assertStringContainsString('Hapus', $htmlButton);

        // Empty state
        $htmlEmpty = Blade::render('<x-empty-state title="Data Kosong" description="Silakan tambah data baru" icon="orders" actionText="Tambah" actionUrl="/tambah" />');
        $this->assertStringContainsString('Data Kosong', $htmlEmpty);
        $this->assertStringContainsString('Silakan tambah data baru', $htmlEmpty);
        $this->assertStringContainsString('Tambah', $htmlEmpty);
    }

    /**
     * Test form controls (input, select, textarea).
     */
    public function test_form_components_render_correctly(): void
    {
        // Form input
        $htmlInput = Blade::render('<x-form.input name="customer_name" label="Nama Pelanggan" placeholder="Masukkan nama" required />');
        $this->assertStringContainsString('Nama Pelanggan', $htmlInput);
        $this->assertStringContainsString('name="customer_name"', $htmlInput);
        $this->assertStringContainsString('placeholder="Masukkan nama"', $htmlInput);
        $this->assertStringContainsString('required', $htmlInput);

        // Form select
        $options = ['kiloan' => 'Kiloan', 'satuan' => 'Satuan'];
        $htmlSelect = Blade::render('<x-form.select name="unit" label="Satuan" :options="$options" selected="kiloan" />', ['options' => $options]);
        $this->assertStringContainsString('Satuan', $htmlSelect);
        $this->assertStringContainsString('name="unit"', $htmlSelect);
        $this->assertStringContainsString('selected', $htmlSelect);

        // Form textarea
        $htmlTextarea = Blade::render('<x-form.textarea name="notes" label="Catatan" placeholder="Catatan khusus..." />');
        $this->assertStringContainsString('Catatan', $htmlTextarea);
        $this->assertStringContainsString('name="notes"', $htmlTextarea);
        $this->assertStringContainsString('placeholder="Catatan khusus..."', $htmlTextarea);
    }

    /**
     * Test confirm-modal component.
     */
    public function test_confirm_modal_component_renders_correctly(): void
    {
        $htmlModal = Blade::render('<x-confirm-modal id="test-confirm" title="Hapus Data" message="Yakin ingin menghapus?" confirmText="Ya, Hapus" confirmVariant="danger" />');
        $this->assertStringContainsString('id="test-confirm"', $htmlModal);
        $this->assertStringContainsString('Hapus Data', $htmlModal);
        $this->assertStringContainsString('Yakin ingin menghapus?', $htmlModal);
        $this->assertStringContainsString('btn-danger', $htmlModal);
        $this->assertStringContainsString('Ya, Hapus', $htmlModal);
    }

    /**
     * Test pagination component renders.
     */
    public function test_pagination_component_renders(): void
    {
        $items = collect(range(1, 20));
        $paginator = new LengthAwarePaginator($items->forPage(1, 5), 20, 5, 1);

        $htmlPagination = Blade::render('<x-pagination :paginator="$paginator" />', ['paginator' => $paginator]);
        $this->assertStringContainsString('pagination-wrap', $htmlPagination);
        $this->assertStringContainsString('Menampilkan', $htmlPagination);
        $this->assertStringContainsString('dari', $htmlPagination);
    }

    /**
     * Test print stylesheet hides sidebar, topbar, navbar, buttons, and alerts.
     */
    public function test_print_stylesheet_rules_in_css(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@media print', $css);
        $this->assertStringContainsString('.app-sidebar', $css);
        $this->assertStringContainsString('.app-topbar', $css);
        $this->assertStringContainsString('.no-print', $css);
        $this->assertStringContainsString('display: none !important;', $css);
    }
}
