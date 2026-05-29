<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\GameHashCompatibility;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\System;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementType;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);
uses(TestsEmulatorUserAgent::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    $this->seedEmulatorUserAgents();
    $this->createConnectUser();

    Role::create(['name' => Role::DEVELOPER, 'display' => 1]);
    Role::create(['name' => Role::DEVELOPER_JUNIOR, 'display' => 2]);

    Storage::fake('media');
    Storage::fake('s3');
    config(['filesystems.disks.s3.key' => 'test']);
});

describe('valid badge', function () {
    test('can be uploaded by developer', function () {
        $this->user->assignRole(Role::DEVELOPER);

        $this->post('doupload.php?r=uploadbadgeimage', [
                'u' => $this->user->display_name,
                't' => $this->user->connect_token,
                'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
            ])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => ['BadgeIter' => '00001'],
            ]);

        Storage::disk('media')->assertExists('Badge/00001.png');
        Storage::disk('media')->assertExists('Badge/00001_lock.png');
        Storage::disk('s3')->assertExists('Badge/00001.png');
        Storage::disk('s3')->assertExists('Badge/00001_lock.png');
    });

    test('can be uploaded by junior developer', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);

        $this->post('doupload.php?r=uploadbadgeimage', [
                'u' => $this->user->display_name,
                't' => $this->user->connect_token,
                'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
            ])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => ['BadgeIter' => '00001'],
            ]);

        Storage::disk('media')->assertExists('Badge/00001.png');
        Storage::disk('media')->assertExists('Badge/00001_lock.png');
        Storage::disk('s3')->assertExists('Badge/00001.png');
        Storage::disk('s3')->assertExists('Badge/00001_lock.png');
    });

    test('cannot be uploaded by non-developer', function () {
        $this->post('doupload.php?r=uploadbadgeimage', [
                'u' => $this->user->display_name,
                't' => $this->user->connect_token,
                'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
            ])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'You must be a developer to upload badge images.',
            ]);

        Storage::disk('media')->assertCount('Badge/', 0);
        Storage::disk('s3')->assertCount('Badge/', 0);
    });

    test('cannot be uploaded anonymously', function () {
        $this->post('doupload.php?r=uploadbadgeimage', [
                'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
            ])
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Missing Token',
            ]);

        Storage::disk('media')->assertCount('Badge/', 0);
        Storage::disk('s3')->assertCount('Badge/', 0);
    });

    test('cannot be uploaded without token', function () {
        $this->post('doupload.php?r=uploadbadgeimage', [
                'u' => $this->user->display_name,
                'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
            ])
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Missing Token',
            ]);

        Storage::disk('media')->assertCount('Badge/', 0);
        Storage::disk('s3')->assertCount('Badge/', 0);
    });

    test('cannot be uploaded with wrong token', function () {
        $this->post('doupload.php?r=uploadbadgeimage', [
                'u' => $this->user->display_name,
                't' => 'LetMeIn',
                'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
            ])
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Error' => "Unknown Request: 'uploadbadgeimage'", // this is clearly wrong
            ]);

        Storage::disk('media')->assertCount('Badge/', 0);
        Storage::disk('s3')->assertCount('Badge/', 0);
    });

    test('is resized to 64x64', function () {
        $this->user->assignRole(Role::DEVELOPER);

        $this->post('doupload.php?r=uploadbadgeimage', [
                'u' => $this->user->display_name,
                't' => $this->user->connect_token,
                'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 160, 128),
            ])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => ['BadgeIter' => '00001'],
            ]);

        Storage::disk('media')->assertExists('Badge/00001.png');
        $contents = Storage::disk('media')->get('Badge/00001.png');
        [$width, $height] = getimagesizefromstring($contents);
        $this->assertEquals(64, $width);
        $this->assertEquals(64, $height);

        $s3contents = Storage::disk('s3')->get('Badge/00001.png');
        $this->assertSame($contents, $s3contents);

        $contents = Storage::disk('media')->get('Badge/00001_lock.png');
        [$width, $height] = getimagesizefromstring($contents);
        $this->assertEquals(64, $width);
        $this->assertEquals(64, $height);

        $s3contents = Storage::disk('s3')->get('Badge/00001_lock.png');
        $this->assertSame($contents, $s3contents);
    });

    test('new id ignores existing files', function () {
        $this->user->assignRole(Role::DEVELOPER);

        Storage::disk('media')->put('Badge/00001.png', 'dummy data');

        $this->post('doupload.php?r=uploadbadgeimage', [
                'u' => $this->user->display_name,
                't' => $this->user->connect_token,
                'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 160, 128),
            ])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => ['BadgeIter' => '00002'],
            ]);

        Storage::disk('media')->assertExists('Badge/00002.png');
        Storage::disk('media')->assertExists('Badge/00002_lock.png');
        Storage::disk('s3')->assertExists('Badge/00002.png');
        Storage::disk('s3')->assertExists('Badge/00002_lock.png');
    });

    test('new id after highest id in database', function () {
        $this->user->assignRole(Role::DEVELOPER);

        Achievement::factory()->create(['image_name' => 12345]);

        $this->post('doupload.php?r=uploadbadgeimage', [
                'u' => $this->user->display_name,
                't' => $this->user->connect_token,
                'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 160, 128),
            ])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => ['BadgeIter' => '12346'],
            ]);

        Storage::disk('media')->assertExists('Badge/12346.png');
        Storage::disk('media')->assertExists('Badge/12346_lock.png');
        Storage::disk('s3')->assertExists('Badge/12346.png');
        Storage::disk('s3')->assertExists('Badge/12346_lock.png');
    });

    test('bmp is not supported', function () {
        $this->user->assignRole(Role::DEVELOPER);

        $this->post('doupload.php?r=uploadbadgeimage', [
                'u' => $this->user->display_name,
                't' => $this->user->connect_token,
                'file' => UploadedFile::fake()->image('uploadbadgeimage.bmp', 64, 64),
            ])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Invalid file type',
            ]);

        Storage::disk('media')->assertCount('Badge/', 0);
    });
});
