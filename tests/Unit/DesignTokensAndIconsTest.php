<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Blade;

class DesignTokensAndIconsTest extends TestCase
{
    /**
     * Test CSS files exist and contain required brand reference color tokens.
     */
    public function test_css_files_contain_all_required_brand_color_tokens(): void
    {
        $resourcesCss = resource_path('css/app.css');
        $publicCss = public_path('css/app.css');

        $this->assertFileExists($resourcesCss);
        $this->assertFileExists($publicCss);

        $resourcesContent = file_get_contents($resourcesCss);
        $publicContent = file_get_contents($publicCss);

        $requiredTokens = [
            '#2563EB', // Biru utama acuan
            '#1D4ED8', // Biru gelap acuan
            '#DBEAFE', // Biru muda acuan
            '#F4F8FF', // Latar acuan
            '#172554', // Teks utama acuan
            '--primary:',
            '--primary-hover:',
            '--primary-light:',
            '--bg-page:',
            '--text-main:',
            '--status-success:',
            '--status-warning:',
            '--status-danger:',
            '--status-info:',
        ];

        foreach ($requiredTokens as $token) {
            $this->assertStringContainsString($token, $resourcesContent, "resources/css/app.css is missing token: {$token}");
            $this->assertStringContainsString($token, $publicContent, "public/css/app.css is missing token: {$token}");
        }
    }

    /**
     * Test CSS files define spacing, radius, shadow, blur, and typography tokens.
     */
    public function test_css_files_contain_spacing_radius_shadow_blur_and_typography_tokens(): void
    {
        $cssContent = file_get_contents(resource_path('css/app.css'));

        // Spacing tokens
        $this->assertStringContainsString('--space-1:', $cssContent);
        $this->assertStringContainsString('--space-4:', $cssContent);
        $this->assertStringContainsString('--space-6:', $cssContent);

        // Radius tokens
        $this->assertStringContainsString('--radius-sm:', $cssContent);
        $this->assertStringContainsString('--radius-md:', $cssContent);
        $this->assertStringContainsString('--radius-lg:', $cssContent);
        $this->assertStringContainsString('--radius-full:', $cssContent);

        // Shadow tokens
        $this->assertStringContainsString('--shadow-sm:', $cssContent);
        $this->assertStringContainsString('--shadow-md:', $cssContent);
        $this->assertStringContainsString('--shadow-glass:', $cssContent);

        // Blur tokens
        $this->assertStringContainsString('--blur-sm:', $cssContent);
        $this->assertStringContainsString('--blur-lg:', $cssContent);

        // Typography tokens
        $this->assertStringContainsString('--font-sans:', $cssContent);
        $this->assertStringContainsString('--text-sm:', $cssContent);
        $this->assertStringContainsString('--text-base:', $cssContent);
        $this->assertStringContainsString('--text-xl:', $cssContent);
    }

    /**
     * Test zero external dependencies (no external font or CDN URLs).
     */
    public function test_css_files_have_zero_external_dependencies(): void
    {
        $cssContent = file_get_contents(resource_path('css/app.css'));

        $this->assertStringNotContainsString('fonts.googleapis.com', $cssContent);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $cssContent);
        $this->assertStringNotContainsString('unpkg.com', $cssContent);
        $this->assertStringNotContainsString('jsdelivr.net', $cssContent);
        $this->assertStringNotContainsString('http://', $cssContent);
        $this->assertStringNotContainsString('https://', $cssContent);
    }

    /**
     * Test that all essential local SVG icon files exist and are valid.
     */
    public function test_all_local_svg_icons_exist_and_are_valid(): void
    {
        $iconsDir = public_path('icons');
        $this->assertDirectoryExists($iconsDir);

        $expectedIcons = [
            'dashboard',
            'orders',
            'customers',
            'services',
            'payments',
            'reports',
            'users',
            'settings',
            'print',
            'check',
            'search',
            'plus',
            'edit',
            'trash',
            'eye',
            'filter',
            'calendar',
            'x',
            'alert-triangle',
            'info',
            'arrow-left',
            'arrow-right',
            'receipt',
            'clock',
        ];

        foreach ($expectedIcons as $iconName) {
            $filePath = "{$iconsDir}/{$iconName}.svg";
            $this->assertFileExists($filePath, "Icon file missing: {$iconName}.svg");

            $content = file_get_contents($filePath);
            $this->assertStringContainsString('<svg', $content, "Invalid SVG in {$iconName}.svg");
            $this->assertStringContainsString('viewBox="0 0 24 24"', $content, "Missing standard viewBox in {$iconName}.svg");
        }
    }

    /**
     * Test Blade icon component renders correctly.
     */
    public function test_blade_icon_component_renders_correctly(): void
    {
        $rendered = Blade::render('<x-icon name="dashboard" size="24" class="custom-icon" />');

        $this->assertStringContainsString('<svg', $rendered);
        $this->assertStringContainsString('class="icon custom-icon"', $rendered);
        $this->assertStringContainsString('width="24"', $rendered);
        $this->assertStringContainsString('height="24"', $rendered);
    }
}
