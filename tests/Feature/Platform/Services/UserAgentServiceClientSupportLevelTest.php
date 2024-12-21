<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services;

use App\Enums\ClientSupportLevel;
use App\Models\Emulator;
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
}
