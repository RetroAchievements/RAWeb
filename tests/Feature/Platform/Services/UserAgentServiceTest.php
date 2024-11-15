<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services;

use App\Platform\Services\UserAgentService;
use Tests\TestCase;

class UserAgentServiceTest extends TestCase
{
    private function parseUserAgent(string $userAgent): array
    {
        $service = new UserAgentService();

        return $service->decode($userAgent);
    }

    public function testEmptyUserAgent(): void
    {
        $userAgent = '';

        $this->assertEquals([
            'client' => 'Unknown',
            'clientVersion' => 'Unknown',
        ], $this->parseUserAgent($userAgent));
    }

    public function testNotProvidedUserAgent(): void
    {
        $userAgent = '[not provided]';

        $this->assertEquals([
            'client' => 'Unknown',
            'clientVersion' => 'Unknown',
        ], $this->parseUserAgent($userAgent));
    }

    public function testNoVersionUserAgent(): void
    {
        $userAgent = 'MyApp';

        $this->assertEquals([
            'client' => 'MyApp',
            'clientVersion' => 'Unknown',
        ], $this->parseUserAgent($userAgent));
    }

    public function testVersionOnlyUserAgent(): void
    {
        $userAgent = '1.2.3';

        $this->assertEquals([
            'client' => '1.2.3',
            'clientVersion' => 'Unknown',
        ], $this->parseUserAgent($userAgent));
    }

    public function testRALibretroUserAgentWithCore(): void
    {
        $userAgent = 'RALibRetro/1.3.11 (WindowsNT 10.0) Integration/1.0.4.0 fceumm_libretro/(SVN)_58030a3';

        $this->assertEquals([
            'client' => 'RALibRetro',
            'clientVersion' => '1.3.11',
            'os' => 'WindowsNT 10.0',
            'integrationVersion' => '1.0.4.0',
            'clientVariation' => 'fceumm',
            'extra' => [
                'fceumm_libretro' => '(SVN)_58030a3',
            ],
        ], $this->parseUserAgent($userAgent));
    }

    public function testRetroArchUserAgent(): void
    {
        $userAgent = 'RetroArch/1.11.0 (Linux 5.0)';

        $this->assertEquals([
            'client' => 'RetroArch',
            'clientVersion' => '1.11.0',
            'os' => 'Linux 5.0',
        ], $this->parseUserAgent($userAgent));
    }

    public function testRetroArchUserAgentWithCore(): void
    {
        $userAgent = 'RetroArch/1.8.1 (Windows 10 x64 Build 18362 10.0) quicknes_libretro/1.0-WIP_7c0796d';

        $this->assertEquals([
            'client' => 'RetroArch',
            'clientVersion' => '1.8.1',
            'os' => 'Windows 10 x64 Build 18362 10.0',
            'clientVariation' => 'quicknes',
            'extra' => [
                'quicknes_libretro' => '1.0-WIP_7c0796d',
            ],
        ], $this->parseUserAgent($userAgent));
    }

    public function testRetroArchPreUserAgent(): void
    {
        $userAgent = 'libretro';

        $this->assertEquals([
            'client' => 'RetroArch',
            'clientVersion' => 'Unknown',
        ], $this->parseUserAgent($userAgent));

        $this->assertEquals(-1, UserAgentService::versionCompare('Unknown', '1.8.1')); // Unknown < 1.8.1
    }

    public function testRANESUserAgent(): void
    {
        $userAgent = 'RANes/1.1.0.0 (WindowsNT 10.0) Integration/1.0.4.0';

        $this->assertEquals([
            'client' => 'RANes',
            'clientVersion' => '1.1.0.0',
            'os' => 'WindowsNT 10.0',
            'integrationVersion' => '1.0.4.0',
        ], $this->parseUserAgent($userAgent));
    }

    public function testWinArcadiaUserAgent(): void
    {
        $userAgent = 'WinArcadia/30.8 (WindowsNT 6.2) Integration/1.2.1.0';

        $this->assertEquals([
            'client' => 'WinArcadia',
            'clientVersion' => '30.8',
            'os' => 'WindowsNT 6.2',
            'integrationVersion' => '1.2.1.0',
        ], $this->parseUserAgent($userAgent));
    }

    public function testChromeUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.71 Safari/537.36';

        $this->assertEquals([
            'client' => 'Mozilla',
            'clientVersion' => '5.0',
            'os' => 'Windows NT 10.0',
            'extra' => [
                'AppleWebKit' => '537.36',
                'Chrome' => '76.0.3809.71',
                'Safari' => '537.36',
            ],
        ], $this->parseUserAgent($userAgent));
    }

    public function testFirefoxUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:106.0) Gecko/20100101 Firefox/106.0';

        $this->assertEquals([
            'client' => 'Mozilla',
            'clientVersion' => '5.0',
            'os' => 'Windows NT 10.0',
            'extra' => [
                'Gecko' => '20100101',
                'Firefox' => '106.0',
            ],
        ], $this->parseUserAgent($userAgent));
    }

    public function testCurlUserAgent(): void
    {
        $userAgent = 'curl/8.1.2';

        $this->assertEquals([
            'client' => 'curl',
            'clientVersion' => '8.1.2',
        ], $this->parseUserAgent($userAgent));
    }

    public function testPythonUserAgent(): void
    {
        $userAgent = 'python-requests/2.31.0';

        $this->assertEquals([
            'client' => 'python-requests',
            'clientVersion' => '2.31.0',
        ], $this->parseUserAgent($userAgent));
    }

    public function testPCSX2UserAgent(): void
    {
        $userAgent = 'PCSX2 v1.7.5672 (SteamOS 20240228.2)';

        $this->assertEquals([
            'client' => 'PCSX2',
            'clientVersion' => '1.7.5672',
            'os' => 'SteamOS 20240228.2',
        ], $this->parseUserAgent($userAgent));
    }

    public function testPCSX2NightlyUserAgent(): void
    {
        $userAgent = 'PCSX2 Nightly - v1.7.3366 (Microsoft Windows 10)';

        $this->assertEquals([
            'client' => 'PCSX2',
            'clientVersion' => '1.7.3366',
            'os' => 'Microsoft Windows 10',
        ], $this->parseUserAgent($userAgent));
    }

    public function testPPSSPPUserAgent(): void
    {
        $userAgent = 'PPSSPP/v1.17.1';

        $this->assertEquals([
            'client' => 'PPSSPP',
            'clientVersion' => '1.17.1',
        ], $this->parseUserAgent($userAgent));

        $this->assertEquals(-1, UserAgentService::versionCompare('PPSSPP/1.17.1-103.20240327git768174e.fc40', 'PPSSPP/v1.17.5'));
    }

    public function testDuckStationForAndroidUserAgent(): void
    {
        $userAgent = 'DuckStation for Android (arm64) 0.1-6291-ga38d6928';

        $this->assertEquals([
            'client' => 'DuckStation',
            'clientVersion' => '0.1-6291-ga38d6928',
            'os' => 'arm64',
        ], $this->parseUserAgent($userAgent));
    }

    public function testBizHawkUserAgent(): void
    {
        $userAgent = 'BizHawk/2.9.1 (WindowsNT 10.0) Integration/1.2.1.0';

        $this->assertEquals([
            'client' => 'BizHawk',
            'clientVersion' => '2.9.1',
            'os' => 'WindowsNT 10.0',
            'integrationVersion' => '1.2.1.0',
        ], $this->parseUserAgent($userAgent));
    }

    public function testBizHawkStandaloneUserAgent(): void
    {
        $userAgent = 'BizHawk/Version 2.9.2';

        $this->assertEquals([
            'client' => 'BizHawk',
            'clientVersion' => '2.9.2',
        ], $this->parseUserAgent($userAgent));

        // bizhawk changed its user agent in Sep 2024
        // https://discord.com/channels/476211979464343552/757767535293890682/1279957558320173108
        $userAgent = 'EmuHawk/2.9.2';

        $this->assertEquals([
            'client' => 'EmuHawk',
            'clientVersion' => '2.9.2',
        ], $this->parseUserAgent($userAgent));
    }

    public function testBizHawkNightlyUserAgent(): void
    {
        $userAgent = 'BizHawk/GIT HEAD#2206571';

        $this->assertEquals([
            'client' => 'BizHawk',
            'clientVersion' => 'GIT',
        ], $this->parseUserAgent($userAgent));
    }

    public function testHorizonUserAgent(): void
    {
        $userAgent = 'HorizonXI/1.0.1';

        $this->assertEquals([
            'client' => 'HorizonXI',
            'clientVersion' => '1.0.1',
        ], $this->parseUserAgent($userAgent));
    }

    public function testAetherSX2UserAgent(): void
    {
        $userAgent = 'AetherSX2 v1.4-3064-gf36e49ad1 (Android)';

        $this->assertEquals([
            'client' => 'AetherSX2',
            'clientVersion' => '1.4-3064-gf36e49ad1',
            'os' => 'Android',
        ], $this->parseUserAgent($userAgent));

        $userAgent = 'AetherSX2 V-1.22-BlackHawk (Android)';

        $this->assertEquals([
            'client' => 'AetherSX2',
            'clientVersion' => '1.22-BlackHawk',
            'os' => 'Android',
        ], $this->parseUserAgent($userAgent));

        $this->assertEquals(-1, UserAgentService::versionCompare('1.4-3064-gf36e49ad1', '1.4-3065-abcdef0123')); // 3064 < 3065
        $this->assertEquals(-1, UserAgentService::versionCompare('1.4-3064-gf36e49ad1', '1.22-BlackHawk')); // 1.4 < 1.22
        $this->assertEquals(-1, UserAgentService::versionCompare('1.4-3064-gf36e49ad1', '1.5-4248-g3a6307d69')); // 1.4 < 1.5
    }

    public function testEmuchievementsUserAgent(): void
    {
        $userAgent = 'Emuchievements/2.0.3 (+https://github.com/EmuDeck/Emuchievements)';

        $this->assertEquals([
            'client' => 'Emuchievements',
            'clientVersion' => '2.0.3',
            'os' => '+https://github.com/EmuDeck/Emuchievements',
        ], $this->parseUserAgent($userAgent));
    }

    public function testKodiUserAgent(): void
    {
        $userAgent = 'Kodi/20.5 (Linux; Android 11.0; Lenovo TB-X306F Build/RP1A.200720.011) Android/11.0.0 Sys_CPU/aarch64 App_Bitness/64 Version/20.5-(20.5.0)-Git:20240303-f869066a2e';

        $this->assertEquals([
            'client' => 'Kodi',
            'clientVersion' => '20.5',
            'os' => 'Linux',
            'extra' => [
                'Android' => '11.0.0',
                'Sys_CPU' => 'aarch64',
                'App_Bitness' => '64',
                'Version' => '20.5-(20.5.0)-Git:20240303-f869066a2e',
            ],
        ], $this->parseUserAgent($userAgent));
    }

    public function testMelonDSBetaUserAgent(): void
    {
        $userAgent = 'melonDS-android/beta-1.9.3-ps';

        $this->assertEquals([
            'client' => 'melonDS-android',
            'clientVersion' => 'beta-1.9.3-ps',
        ], $this->parseUserAgent($userAgent));

        $this->assertEquals(-1, UserAgentService::versionCompare('beta-1.9.3-ps', '2.9.1'));
        $this->assertEquals(1, UserAgentService::versionCompare('beta-1.9.3-ps', '1.9.1'));
    }

    public function testDolphinAlphaUserAgent(): void
    {
        $userAgent = 'Dolphin/e5d32f273f (WindowsNT 10.0) Integration/1.2.1.47-alpha Dolphin e5d32f273f retroachievements-developer-testing';

        $this->assertEquals([
            'client' => 'Dolphin',
            'clientVersion' => 'e5d32f273f',
            'os' => 'WindowsNT 10.0',
            'integrationVersion' => '1.2.1.47-alpha',
        ], $this->parseUserAgent($userAgent));
    }

    public function testDolphinUserAgent(): void
    {
        $userAgent = 'Dolphin/2407-123';

        $this->assertEquals([
            'client' => 'Dolphin',
            'clientVersion' => '2407-123',
        ], $this->parseUserAgent($userAgent));

        $userAgent = 'Dolphin/2407-171-dirty';

        $this->assertEquals([
            'client' => 'Dolphin',
            'clientVersion' => '2407-171-dirty',
        ], $this->parseUserAgent($userAgent));

        $userAgent = 'Dolphin/5.0-21878';

        $this->assertEquals([
            'client' => 'Dolphin',
            'clientVersion' => '5.0-21878',
        ], $this->parseUserAgent($userAgent));

        // dolphin changed user agent from 5.0-build to YYMM-build in July 2024
        $this->assertEquals(-1, UserAgentService::versionCompare('2407-123', '2407-123-dirty')); // 2407-123 < 2407-123-dirty
        $this->assertEquals(-1, UserAgentService::versionCompare('2407-5', '2409-17')); // 2407-5 < 2409-17
        $this->assertEquals(1, UserAgentService::versionCompare('2409-67', '5.0-21878')); // 2409-67 > 5.0-21878
    }

    public function testFlycastUserAgent(): void
    {
        $userAgent = 'Flycast/45bf218df CFNetwork/1498.700.2 Darwin/23.6.0';

        $this->assertEquals([
            'client' => 'Flycast',
            'clientVersion' => '45bf218df',
            'extra' => [
                'CFNetwork' => '1498.700.2',
                'Darwin' => '23.6.0',
            ],
        ], $this->parseUserAgent($userAgent));

        // we can't enforce minimum versions on git hashes
        $this->assertEquals(0, UserAgentService::versionCompare('45bf218df', '2d4684a46'));
    }

    public function testNetherSX2UserAgent(): void
    {
        $userAgent = 'NetherSX2 v1.9a-dev-g3a6307d69 (Android)';

        $this->assertEquals([
            'client' => 'NetherSX2',
            'clientVersion' => '1.9a-dev-g3a6307d69',
            'os' => 'Android',
        ], $this->parseUserAgent($userAgent));

        $this->assertEquals(-1, UserAgentService::versionCompare('1.9a-dev-93a630769', '1.9b-dev-12b3dc45'));
        $this->assertEquals(-1, UserAgentService::versionCompare('1.9a-dev-93a630769', '1.9B-dev-12b3dc45'));
        $this->assertEquals(-1, UserAgentService::versionCompare('1.9-dev-ab3d3dca2', '1.9a-dev-12b3dc45'));
    }
}
