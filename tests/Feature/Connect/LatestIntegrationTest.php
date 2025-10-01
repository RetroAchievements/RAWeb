<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\IntegrationRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatestIntegrationTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testLatestIntegration(): void
    {
        // no data (test site)
        $this->get($this->apiUrl('latestintegration'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => '0.0',
                'LatestVersion' => '0.0',
                'LatestVersionUrl' => 'http://retroachievements.org/bin/RA_Integration.dll',
                'LatestVersionUrlX64' => 'http://retroachievements.org/bin/RA_Integration-x64.dll',
            ]);

        $integration_older = IntegrationRelease::create(['version' => '1.3', 'created_at' => '2024-04-17', 'stable' => 1]);
        $integration_minimum = IntegrationRelease::create(['version' => '1.3.1', 'created_at' => '2024-08-28', 'stable' => 1, 'minimum' => 1]);

        // minimum is latest
        $this->get($this->apiUrl('latestintegration'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $integration_minimum->version,
                'LatestVersion' => $integration_minimum->version,
                'LatestVersionUrl' => 'http://retroachievements.org/bin/RA_Integration.dll',
                'LatestVersionUrlX64' => 'http://retroachievements.org/bin/RA_Integration-x64.dll',
            ]);

        // new beta release is ignored
        $integration_beta = IntegrationRelease::create(['version' => '1.3.1.111', 'created_at' => '2025-09-25']);
        $this->get($this->apiUrl('latestintegration'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $integration_minimum->version,
                'LatestVersion' => $integration_minimum->version,
                'LatestVersionUrl' => 'http://retroachievements.org/bin/RA_Integration.dll',
                'LatestVersionUrlX64' => 'http://retroachievements.org/bin/RA_Integration-x64.dll',
            ]);

        // new official release
        $integration_latest = IntegrationRelease::create(['version' => '1.4', 'created_at' => '2025-10-06', 'stable' => 1]);
        $this->get($this->apiUrl('latestintegration'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $integration_minimum->version,
                'LatestVersion' => $integration_latest->version,
                'LatestVersionUrl' => 'http://retroachievements.org/bin/RA_Integration.dll',
                'LatestVersionUrlX64' => 'http://retroachievements.org/bin/RA_Integration-x64.dll',
            ]);

        // minimum updated
        $integration_latest->minimum = true;
        $integration_latest->save();

        // IntegrationRelease should automatically clear minimum on previous minimum
        $integration_minimum->refresh();
        $this->assertEquals(false, $integration_minimum->minimum);

        $this->get($this->apiUrl('latestintegration'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'MinimumVersion' => $integration_latest->version,
                'LatestVersion' => $integration_latest->version,
                'LatestVersionUrl' => 'http://retroachievements.org/bin/RA_Integration.dll',
                'LatestVersionUrlX64' => 'http://retroachievements.org/bin/RA_Integration-x64.dll',
            ]);
    }
}
