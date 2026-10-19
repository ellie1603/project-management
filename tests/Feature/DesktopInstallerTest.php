<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesktopInstallerTest extends TestCase
{
    private const WINDOWS_BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0';

    private const DESKTOP_APP = self::WINDOWS_BROWSER.' BMPCDesktop/1.0.0';

    private const MAC_BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15';

    private string $installer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installer = tempnam(sys_get_temp_dir(), 'bmpc-setup');
        file_put_contents($this->installer, 'MZ fake installer');
        config(['desktop.installer_path' => $this->installer]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->installer)) {
            unlink($this->installer);
        }

        parent::tearDown();
    }

    public function test_login_page_offers_the_desktop_app_to_windows_browsers(): void
    {
        $this->withHeader('User-Agent', self::WINDOWS_BROWSER)
            ->get('/login')
            ->assertOk()
            ->assertSee('Install the desktop app?')
            ->assertSee(route('desktop.installer'), false)
            ->assertSee('Not now');
    }

    public function test_offer_is_hidden_inside_the_desktop_app(): void
    {
        $this->withHeader('User-Agent', self::DESKTOP_APP)
            ->get('/login')
            ->assertOk()
            ->assertDontSee('Install the desktop app?');
    }

    public function test_offer_is_hidden_on_other_operating_systems(): void
    {
        $this->withHeader('User-Agent', self::MAC_BROWSER)
            ->get('/login')
            ->assertOk()
            ->assertDontSee('Install the desktop app?');
    }

    public function test_offer_is_hidden_until_an_installer_is_published(): void
    {
        unlink($this->installer);

        $this->withHeader('User-Agent', self::WINDOWS_BROWSER)
            ->get('/login')
            ->assertOk()
            ->assertDontSee('Install the desktop app?');
    }

    public function test_install_downloads_the_published_installer(): void
    {
        $this->get(route('desktop.installer'))
            ->assertOk()
            ->assertDownload('BMPC-Desktop-Setup.exe')
            ->assertHeader('Content-Type', 'application/vnd.microsoft.portable-executable');
    }

    public function test_download_is_not_found_without_an_installer(): void
    {
        unlink($this->installer);

        $this->get(route('desktop.installer'))->assertNotFound();
    }
}
