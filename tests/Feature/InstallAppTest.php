<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_app_manifest_makes_the_site_installable(): void
    {
        $response = $this->get('/manifest.webmanifest')->assertOk();

        $this->assertStringStartsWith('application/manifest+json', $response->headers->get('Content-Type'));
        $response->assertJson([
            'short_name' => 'BMPC',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
        ]);
        $this->assertContains('maskable', array_column($response->json('icons'), 'purpose'));

        foreach ($response->json('icons') as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
        $this->assertFileExists(public_path('sw.js'));
    }

    public function test_every_page_links_the_manifest(): void
    {
        $this->get('/login')->assertSee('rel="manifest"', false)->assertSee('apple-mobile-web-app-capable', false);

        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($user)->get('/dashboard')->assertSee('rel="manifest"', false);
    }

    public function test_one_install_link_works_for_every_device(): void
    {
        $this->get(route('app.install'))
            ->assertOk()
            ->assertSee('Install BMPC')
            ->assertSee('data-install-for="android"', false)
            ->assertSee('data-install-for="ios"', false)
            ->assertSee('data-install-for="desktop"', false)
            ->assertSee('Add to Home Screen')
            ->assertSee('Open in your browser');
    }

    public function test_the_windows_download_is_offered_only_when_published(): void
    {
        config(['desktop.installer_path' => storage_path('framework/testing/missing-installer.exe')]);
        $this->get(route('app.install'))->assertDontSee(route('desktop.installer'));

        $path = tempnam(sys_get_temp_dir(), 'inst');
        config(['desktop.installer_path' => $path]);
        $this->get(route('app.install'))->assertSee(route('desktop.installer'));
        @unlink($path);
    }

    public function test_the_login_page_links_to_the_install_page(): void
    {
        $this->get('/login')->assertSee(route('app.install'));
    }
}
