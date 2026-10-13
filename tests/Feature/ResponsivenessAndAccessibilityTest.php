<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsivenessAndAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->create([
            'role' => UserRole::Kasir,
            'is_active' => true,
        ]);
    }

    /**
     * Test desktop dashboard rendering and structural layout elements.
     */
    public function test_desktop_dashboard_and_table_layout_elements(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('app-shell');
        $response->assertSee('app-sidebar');
        $response->assertSee('app-main');
        $response->assertSee('app-topbar');
        $response->assertSee('table-container');

        $css = file_get_contents(resource_path('css/app.css'));

        // Grid desktop rules
        $this->assertStringContainsString('.grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }', $css);
        $this->assertStringContainsString('width: 260px;', $css);

        // Table container overflow for responsive tables
        $this->assertStringContainsString('.table-container', $css);
        $this->assertStringContainsString('overflow-x: auto;', $css);
        $this->assertStringContainsString('-webkit-overflow-scrolling: touch;', $css);
    }

    /**
     * Test tablet breakpoint and touch-friendly controls.
     */
    public function test_tablet_breakpoint_and_touch_target_rules(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Tablet breakpoint (< 1024px)
        $this->assertStringContainsString('@media (max-width: 1024px)', $css);
        $this->assertStringContainsString('.mobile-toggle-btn {', $css);
        $this->assertStringContainsString('transform: translateX(-100%);', $css);

        // Touch target sizing (minimum 42px - 44px)
        $this->assertStringContainsString('min-height: 44px;', $css);
        $this->assertStringContainsString('min-height: 42px;', $css);
    }

    /**
     * Test mobile navigation, grid stacking, and table scroll rules.
     */
    public function test_mobile_screen_navigation_and_stacking(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('dashboard'));

        // Shell contains hamburger button and backdrop for mobile drawer
        $response->assertSee('mobile-toggle-btn');
        $response->assertSee('sidebar-backdrop');

        $css = file_get_contents(resource_path('css/app.css'));

        // Mobile breakpoint (< 768px)
        $this->assertStringContainsString('@media (max-width: 768px)', $css);
        $this->assertStringContainsString('grid-template-columns: 1fr;', $css);
        $this->assertStringContainsString('flex-direction: column;', $css);
    }

    /**
     * Test visible keyboard focus styles for buttons, links, and forms.
     */
    public function test_visible_keyboard_focus_is_strictly_defined(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Visible keyboard focus on buttons
        $this->assertStringContainsString('.btn:focus-visible', $css);
        $this->assertStringContainsString('outline: 2px solid var(--primary);', $css);
        $this->assertStringContainsString('outline-offset: 2px;', $css);

        // Visible focus on navigation links
        $this->assertStringContainsString('.nav-link:focus-visible', $css);

        // Form inputs focus ring
        $this->assertStringContainsString('.form-input:focus', $css);
        $this->assertStringContainsString('border-color: var(--border-focus);', $css);
        $this->assertStringContainsString('box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.20);', $css);
    }

    /**
     * Test prefers-reduced-motion media query.
     */
    public function test_prefers_reduced_motion_is_respected(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('animation-duration: 0.01ms !important;', $css);
        $this->assertStringContainsString('transition-duration: 0.01ms !important;', $css);
        $this->assertStringContainsString('scroll-behavior: auto !important;', $css);
    }

    /**
     * Test solid fallback when blur (backdrop-filter) is unavailable.
     */
    public function test_solid_fallback_when_blur_is_unavailable(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Base solid fallback
        $this->assertStringContainsString('background-color: var(--bg-surface);', $css);
        $this->assertStringContainsString('--bg-surface:       #FFFFFF;', $css);

        // Progressive enhancement using @supports
        $this->assertStringContainsString('@supports (backdrop-filter: blur(12px)) or (-webkit-backdrop-filter: blur(12px))', $css);
        $this->assertStringContainsString('backdrop-filter: blur(var(--blur-lg));', $css);
    }
}
