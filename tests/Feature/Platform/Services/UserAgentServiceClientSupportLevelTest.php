<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services;

use App\Enums\ClientSupportLevel;
use App\Models\Emulator;
use App\Models\EmulatorCoreRestriction;
use App\Models\EmulatorUserAgent;
use App\Platform\Services\UserAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAgentServiceClientSupportLevelTest extends TestCase
{
    use RefreshDatabase;

    public function testClientSupportLevel(): void
    {
        $userAgentService = new UserAgentService();

        $genericClient = Emulator::create(['name' => 'Generic Client']);
        EmulatorUserAgent::create([
            'emulator_id' => $genericClient->id,
            'client' => 'GenericClient',
        ]);

        EmulatorUserAgent::create([
            'emulator_id' => Emulator::create(['name' => 'Restricted Client'])->id,
            'client' => 'RestrictedClient',
            'minimum_allowed_version' => '1.2',
        ]);

        EmulatorUserAgent::create([
            'emulator_id' => Emulator::create(['name' => 'Deprecated Client'])->id,
            'client' => 'DeprecatedClient',
            'minimum_hardcore_version' => '1.6',
        ]);

        EmulatorUserAgent::create([
            'emulator_id' => Emulator::create(['name' => 'Restricted Deprecated Client'])->id,
            'client' => 'RestrictedDeprecatedClient',
            'minimum_allowed_version' => '1.2',
            'minimum_hardcore_version' => '1.6',
        ]);

        $multiUserAgentEmulator = Emulator::create(['name' => 'Multiple User Agents Client']);
        EmulatorUserAgent::create([
            'emulator_id' => $multiUserAgentEmulator->id,
            'client' => 'OldUserAgent',
            'minimum_allowed_version' => '999999', // OldUserAgent is no longer allowed at all
        ]);
        EmulatorUserAgent::create([
            'emulator_id' => $multiUserAgentEmulator->id,
            'client' => 'NewUserAgent',
            'minimum_hardcore_version' => '2.1', // NewUserAgent must be at least 2.1
        ]);

        // no user agent = unknown
        $this->assertEquals(ClientSupportLevel::Unknown,
            $userAgentService->getSupportLevel(''));

        $this->assertEquals(ClientSupportLevel::Unknown,
            $userAgentService->getSupportLevel('[not provided]'));

        // unknown user agent = unknown
        $this->assertEquals(ClientSupportLevel::Unknown,
            $userAgentService->getSupportLevel('UnknownClient/1.0'));

        // no restrictions (inactive emulator) = unsupported
        $this->assertEquals(ClientSupportLevel::Unsupported,
            $userAgentService->getSupportLevel('GenericClient/0.0'));

        $this->assertEquals(ClientSupportLevel::Unsupported,
            $userAgentService->getSupportLevel('GenericClient/1.0'));

        $this->assertEquals(ClientSupportLevel::Unsupported,
            $userAgentService->getSupportLevel('GenericClient/16.5'));

        $this->assertEquals(ClientSupportLevel::Unsupported,
            $userAgentService->getSupportLevel('GenericClient/notaversion'));

        // no restrictions (active emulator) = full
        $genericClient->active = true;
        $genericClient->save();

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('GenericClient/0.0'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('GenericClient/1.0'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('GenericClient/16.5'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('GenericClient/notaversion'));

        // minimum allowed version = blocked if older
        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RestrictedClient/0.0'));

        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RestrictedClient/1.1.2'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RestrictedClient/1.2'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RestrictedClient/1.2.1'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RestrictedClient/1.2-branch'));

        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RestrictedClient/notaversion'));

        // minimum hardcore version = outdated if older
        $this->assertEquals(ClientSupportLevel::Outdated,
            $userAgentService->getSupportLevel('DeprecatedClient/0.0'));

        $this->assertEquals(ClientSupportLevel::Outdated,
            $userAgentService->getSupportLevel('DeprecatedClient/1.5.2'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('DeprecatedClient/1.6'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('DeprecatedClient/1.6.1'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('DeprecatedClient/1.6-branch'));

        $this->assertEquals(ClientSupportLevel::Outdated,
            $userAgentService->getSupportLevel('DeprecatedClient/notaversion'));

        // minimum hardcore version = outdated if older, minumum allowed version = blocked if older
        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RestrictedDeprecatedClient/0.0'));

        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RestrictedDeprecatedClient/1.1.2'));

        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RestrictedDeprecatedClient/1.1-branch'));

        $this->assertEquals(ClientSupportLevel::Outdated,
            $userAgentService->getSupportLevel('RestrictedDeprecatedClient/1.2'));

        $this->assertEquals(ClientSupportLevel::Outdated,
            $userAgentService->getSupportLevel('RestrictedDeprecatedClient/1.2-branch'));

        $this->assertEquals(ClientSupportLevel::Outdated,
            $userAgentService->getSupportLevel('RestrictedDeprecatedClient/1.5.2'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RestrictedDeprecatedClient/1.6'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RestrictedDeprecatedClient/1.6.1'));

        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RestrictedDeprecatedClient/notaversion'));

        // multiple user agents for the same emulator each has its own rules
        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('OldUserAgent/1.1.2'));

        $this->assertEquals(ClientSupportLevel::Blocked, // newer than NewUserAgent minimum, but blocked by OldUserAgent
            $userAgentService->getSupportLevel('OldUserAgent/2.5'));

        $this->assertEquals(ClientSupportLevel::Outdated,
            $userAgentService->getSupportLevel('NewUserAgent/1.1.2'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('NewUserAgent/2.5'));
    }

    public function testCoreRestrictionSupportLevel(): void
    {
        $userAgentService = new UserAgentService();

        // set up RetroArch as an active, fully-supported emulator
        $retroArch = Emulator::create(['name' => 'RetroArch', 'active' => true]);
        EmulatorUserAgent::create([
            'emulator_id' => $retroArch->id,
            'client' => 'RetroArch',
            'minimum_hardcore_version' => '1.10',
        ]);

        // create a core restriction that blocks the "dolphin_libretro" core
        EmulatorCoreRestriction::create([
            'core_name' => 'dolphin_libretro',
            'support_level' => ClientSupportLevel::Blocked,
            'notes' => 'accuracy issues',
        ]);

        EmulatorCoreRestriction::create([
            'core_name' => 'doublecherrygb_libretro',
            'support_level' => ClientSupportLevel::Unsupported,
            'recommendation' => 'We recommend using the gambatte core instead.',
            'notes' => 'accuracy issues',
        ]);

        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) dolphin_libretro/df2b1a75'));

        $this->assertEquals(ClientSupportLevel::Unsupported,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) doublecherrygb_libretro/abc123'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) snes9x_libretro/abc123'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) mgba_libretro/0.10.0'));

        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RetroArch/1.22.2'));

        $this->assertEquals(ClientSupportLevel::Outdated,
            $userAgentService->getSupportLevel('RetroArch/1.8.0 (Linux) snes9x_libretro/abc123'));

        $restriction = $userAgentService->getCoreRestrictionForUserAgent('RetroArch/1.22.2 (Linux) dolphin_libretro/df2b1a75');
        $this->assertNotNull($restriction);
        $this->assertEquals('dolphin_libretro', $restriction->core_name);
        $this->assertEquals(ClientSupportLevel::Blocked, $restriction->support_level);

        $restriction = $userAgentService->getCoreRestrictionForUserAgent('RetroArch/1.22.2 (Linux) snes9x_libretro/abc123');
        $this->assertNull($restriction);

        $restriction = $userAgentService->getCoreRestrictionForUserAgent('RetroArch/1.22.2');
        $this->assertNull($restriction);

        $restriction = $userAgentService->getCoreRestrictionForUserAgent('RetroArch/1.22.2 (Linux) doublecherrygb_libretro/abc123');
        $this->assertNotNull($restriction);
        $this->assertEquals('doublecherrygb_libretro', $restriction->core_name);
        $this->assertEquals('We recommend using the gambatte core instead.', $restriction->recommendation);

        // doesn't match
        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) doublecherry_libretro/abc123'));

        EmulatorCoreRestriction::create([
            'core_name' => 'somecore_libretro',
            'support_level' => ClientSupportLevel::Warned,
            'recommendation' => 'Consider using a different core for best results.',
            'notes' => 'accuracy issues',
        ]);

        $this->assertEquals(ClientSupportLevel::Warned,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) somecore_libretro/abc123'));

        $restriction = $userAgentService->getCoreRestrictionForUserAgent('RetroArch/1.22.2 (Linux) somecore_libretro/abc123');
        $this->assertNotNull($restriction);
        $this->assertEquals('somecore_libretro', $restriction->core_name);
        $this->assertEquals(ClientSupportLevel::Warned, $restriction->support_level);
        $this->assertEquals('Consider using a different core for best results.', $restriction->recommendation);
    }

    public function testCoreRestrictionMinimumVersionBypass(): void
    {
        $userAgentService = new UserAgentService();

        $retroArch = Emulator::create(['name' => 'RetroArch', 'active' => true]);
        EmulatorUserAgent::create([
            'emulator_id' => $retroArch->id,
            'client' => 'RetroArch',
            'minimum_hardcore_version' => '1.10',
        ]);

        // blocked restriction with a minimum version bypass at 2.0.0
        EmulatorCoreRestriction::create([
            'core_name' => 'dolphin_libretro',
            'support_level' => ClientSupportLevel::Blocked,
            'minimum_version' => '2.0.0',
            'notes' => 'accuracy issues fixed in 2.0.0',
        ]);

        // unsupported restriction with a minimum version bypass at 1.5.0
        EmulatorCoreRestriction::create([
            'core_name' => 'problemcore_libretro',
            'support_level' => ClientSupportLevel::Unsupported,
            'minimum_version' => '1.5.0',
            'notes' => 'issues fixed in 1.5.0',
        ]);

        // restriction with no minimum_version should always apply
        EmulatorCoreRestriction::create([
            'core_name' => 'alwaysblocked_libretro',
            'support_level' => ClientSupportLevel::Blocked,
            'notes' => 'permanently restricted',
        ]);

        // core version below minimum, so the restriction applies
        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) dolphin_libretro/1.9.9'));

        // core version exactly at minimum, so the restriction is bypassed
        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) dolphin_libretro/2.0.0'));

        // core version above minimum, so the restriction is bypassed
        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) dolphin_libretro/2.1.0'));

        // version with a trailing commit hash suffix still bypasses the restriction
        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) dolphin_libretro/2.0.0_abcdef'));

        // bare commit hash is treated as below any semver, therefore the restriction is not bypassed
        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) dolphin_libretro/df2b1a75'));

        // no minimum_version set, so the restriction always applies
        $this->assertEquals(ClientSupportLevel::Blocked,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) alwaysblocked_libretro/99.99.99'));

        // unsupported restriction with a version below the minimum, so the restriction applies
        $this->assertEquals(ClientSupportLevel::Unsupported,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) problemcore_libretro/1.4.9'));

        // unsupported restriction with a version at the minimum, so the restriction is bypassed
        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) problemcore_libretro/1.5.0'));

        // core version with a "v" prefix at the minimum should also bypass the restriction
        $this->assertEquals(ClientSupportLevel::Full,
            $userAgentService->getSupportLevel('RetroArch/1.22.2 (Linux) dolphin_libretro/v2.0.0'));

        $restriction = $userAgentService->getCoreRestrictionForUserAgent('RetroArch/1.22.2 (Linux) dolphin_libretro/2.0.0');
        $this->assertNull($restriction);

        $restriction = $userAgentService->getCoreRestrictionForUserAgent('RetroArch/1.22.2 (Linux) dolphin_libretro/1.9.9');
        $this->assertNotNull($restriction);
        $this->assertEquals('dolphin_libretro', $restriction->core_name);
        $this->assertEquals(ClientSupportLevel::Blocked, $restriction->support_level);

        $restriction = $userAgentService->getCoreRestrictionForUserAgent('RetroArch/1.22.2 (Linux) dolphin_libretro/df2b1a75');
        $this->assertNotNull($restriction);
        $this->assertEquals('dolphin_libretro', $restriction->core_name);
    }
}
