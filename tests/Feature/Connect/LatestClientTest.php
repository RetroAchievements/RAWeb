<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Emulator;
use App\Models\EmulatorRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LatestClientTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testLatestClient(): void
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $emulator1 = Emulator::create([
            'name' => 'RAEmu',
            'download_url' => 'bin/RAEmu.zip',
            'download_x64_url' => null,
            'active' => 1,
        ]);

        $emulator2 = Emulator::create([
            'name' => 'RAEmu2',
            'download_url' => 'bin/RAEmu2.zip',
            'download_x64_url' => 'bin/RAEmu2-x64.zip',
            'active' => 1,
        ]);
        $emulator2_stable = EmulatorRelease::create(['emulator_id' => $emulator2->id, 'version' => '2.0', 'stable' => 1]);

        $emulator3 = Emulator::create([
            'name' => 'ExternalEmu',
            'download_url' => 'http://external-emu.net/download',
            'download_x64_url' => 'http://external-emu.net/download-x64',
            'active' => 1,
        ]);
        $emulator3_stable = EmulatorRelease::create(['emulator_id' => $emulator3->id, 'version' => '1.3', 'stable' => 1]);

        // unknown client ID
        $this->get($this->apiUrl('latestclient', ['e' => 99]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unknown client',
            ]);

        // pre-release not returned
        $emulator1_beta = EmulatorRelease::create(['emulator_id' => $emulator1->id, 'version' => '0.9', 'stable' => 0]);
        $this->get($this->apiUrl('latestclient', ['e' => $emulator1->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unknown client',
            ]);

        // initial release. x86 only. no minimum specified. will return latest as minimum
        $now = $now->addDays(10);
        Carbon::setTestNow($now);
        $emulator1_1_0 = EmulatorRelease::create(['emulator_id' => $emulator1->id, 'version' => '1.0', 'stable' => 1]);
        $this->get($this->apiUrl('latestclient', ['e' => $emulator1->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $emulator1_1_0->version,
                'LatestVersion' => $emulator1_1_0->version,
                'LatestVersionUrl' => config('app.url') . '/bin/RAEmu.zip',
                'LatestVersionUrlX64' => null,
            ]);

        // beta release. x86 only
        $now = $now->addDays(30);
        Carbon::setTestNow($now);
        $emulator1_1_1_beta = EmulatorRelease::create(['emulator_id' => $emulator1->id, 'version' => '1.0.1', 'stable' => 0]);
        $this->get($this->apiUrl('latestclient', ['e' => $emulator1->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $emulator1_1_0->version,
                'LatestVersion' => $emulator1_1_0->version,
                'LatestVersionUrl' => config('app.url') . '/bin/RAEmu.zip',
                'LatestVersionUrlX64' => null,
            ]);

        // official release. no minimum specified. will return latest as minimum
        $now = $now->addDays(5);
        Carbon::setTestNow($now);
        $emulator1_1_1 = EmulatorRelease::create(['emulator_id' => $emulator1->id, 'version' => '1.1', 'stable' => 1]);
        $this->get($this->apiUrl('latestclient', ['e' => $emulator1->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $emulator1_1_1->version,
                'LatestVersion' => $emulator1_1_1->version,
                'LatestVersionUrl' => config('app.url') . '/bin/RAEmu.zip',
                'LatestVersionUrlX64' => null,
            ]);

        // official release. minimum specified
        $emulator1_1_0->minimum = 1;
        $emulator1_1_0->save();
        $this->get($this->apiUrl('latestclient', ['e' => $emulator1->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $emulator1_1_0->version,
                'LatestVersion' => $emulator1_1_1->version,
                'LatestVersionUrl' => config('app.url') . '/bin/RAEmu.zip',
                'LatestVersionUrlX64' => null,
            ]);

        // discontinued. no longer recognized
        $emulator1->active = false;
        $emulator1->save();
        $this->get($this->apiUrl('latestclient', ['e' => $emulator1->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unknown client',
            ]);

        // alternate emulator
        $this->get($this->apiUrl('latestclient', ['e' => $emulator2->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $emulator2_stable->version,
                'LatestVersion' => $emulator2_stable->version,
                'LatestVersionUrl' => config('app.url') . '/bin/RAEmu2.zip',
                'LatestVersionUrlX64' => config('app.url') . '/bin/RAEmu2-x64.zip',
            ]);

        // external emulator
        $this->get($this->apiUrl('latestclient', ['e' => $emulator3->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $emulator3_stable->version,
                'LatestVersion' => $emulator3_stable->version,
                'LatestVersionUrl' => $emulator3->download_url,
                'LatestVersionUrlX64' => $emulator3->download_x64_url,
            ]);
    }
}
