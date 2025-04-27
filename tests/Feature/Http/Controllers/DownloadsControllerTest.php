<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Emulator;
use App\Models\Platform;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DownloadsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a handful of default platforms.
        Platform::factory()->create(['name' => 'Windows']);
        Platform::factory()->create(['name' => 'macOS']);
        Platform::factory()->create(['name' => 'Linux']);
        Platform::factory()->create(['name' => 'Android']);
        Platform::factory()->create(['name' => 'iOS']);
    }

    public function testIndexRendersCorrectViewWithExpectedProps(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);
        $platform = Platform::factory()->create();
        $emulator = Emulator::factory()->create();

        $emulator->systems()->attach($system);
        $emulator->platforms()->attach($platform);

        // Act
        $response = $this->get(route('download.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('allEmulators')
            ->has('allPlatforms')
            ->has('allSystems')
            ->has('topSystemIds')
            ->has('popularEmulatorsBySystem')
            ->has('userDetectedPlatformId')
            ->has('can')
        );
    }

    public function testIndexFiltersOutInactiveSystems(): void
    {
        // Arrange
        $activeSystem = System::factory()->create(['active' => true]);
        $inactiveSystem = System::factory()->create(['active' => false]);

        // Act
        $response = $this->get(route('download.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('allSystems', 1)
            ->where('allSystems.0.id', $activeSystem->id)
        );
    }

    public function testIndexFiltersOutSpecialSystemIds(): void
    {
        // Arrange
        System::factory()->create([
            'id' => System::Events,
            'active' => true,
        ]);
        System::factory()->create([
            'id' => System::Hubs,
            'active' => true,
        ]);
        System::factory()->create([
            'id' => System::Standalones,
            'active' => true,
        ]);
        $regularSystem = System::factory()->create(['active' => true]);

        // Act
        $response = $this->get(route('download.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('allSystems', 1)
            ->where('allSystems.0.id', $regularSystem->id)
        );
    }

    public function testIndexIncludesUserSelectedSystemId(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);

        // Act
        $response = $this->get(route('download.index', ['system' => $system->id]));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('userSelectedSystemId', $system->id)
        );
    }

    public function testIndexIncludesRequiredEmulatorData(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);
        $platform = Platform::factory()->create();
        $emulator = Emulator::factory()->create([
            'name' => 'Test Emulator',
            'documentation_url' => 'https://example.com/docs',
            'download_url' => 'https://example.com/download',
            'download_x64_url' => 'https://example.com/download-x64',
            'original_name' => 'Original Test Emulator',
            'source_url' => 'https://github.com/example/test',
            'website_url' => 'https://example.com',
        ]);

        $emulator->systems()->attach($system);
        $emulator->platforms()->attach($platform);

        // Act
        $response = $this->get(route('download.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('allEmulators', 1)
            ->where('allEmulators.0.name', 'Test Emulator')
            ->where('allEmulators.0.documentationUrl', 'https://example.com/docs')
            ->where('allEmulators.0.downloadUrl', 'https://example.com/download')
            ->where('allEmulators.0.downloadX64Url', 'https://example.com/download-x64')
            ->where('allEmulators.0.hasOfficialSupport', false)
            ->where('allEmulators.0.originalName', 'Original Test Emulator')
            ->where('allEmulators.0.sourceUrl', 'https://github.com/example/test')
            ->where('allEmulators.0.websiteUrl', 'https://example.com')
            ->has('allEmulators.0.systems')
            ->has('allEmulators.0.platforms')
        );
    }

    public function testIndexUsesDefaultTopSystemsWhenCacheIsEmpty(): void
    {
        // Arrange
        Cache::forget('top-systems');

        System::factory()->create(['ID' => 3, 'active' => true]); // SNES/Super Famicom

        // Act
        $response = $this->get(route('download.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('topSystemIds')
            ->where('topSystemIds.0', 21) // PlayStation 2
            ->where('topSystemIds.1', 12) // PlayStation
            ->where('topSystemIds.2', 5)  // Game Boy Advance
        );
    }

    public function testIndexHandlesEmptyCachedPopularEmulators(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);

        // Act
        $response = $this->get(route('download.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('popularEmulatorsBySystem')
            ->where('popularEmulatorsBySystem.0', [])
            ->where("popularEmulatorsBySystem.{$system->id}", [])
        );
    }
}
