<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\ClaimStatus;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Role;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);
uses(TestsEmulatorUserAgent::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    $this->seedEmulatorUserAgents();
    $this->createConnectUser();

    Role::create(['name' => Role::DEVELOPER, 'display' => 1]);
    Role::create(['name' => Role::DEVELOPER_JUNIOR, 'display' => 2]);
    Role::create(['name' => Role::ARTIST, 'display' => 3]);
    Role::create(['name' => Role::WRITER, 'display' => 4]);

    Storage::fake('media');
    Storage::fake('s3');
    config(['filesystems.disks.s3.key' => 'test']);
});

describe('valid badge', function () {
    test('can be uploaded by a developer', function () {
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

    test('can be uploaded by a junior developer with an active claim', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        AchievementSetClaim::factory()->create(['user_id' => $this->user->id]);

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

    test('cannot be uploaded by a junior developer without any active claims', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        AchievementSetClaim::factory()->create(['user_id' => $this->user->id, 'status' => ClaimStatus::Dropped]);

        $this->post('doupload.php?r=uploadbadgeimage', [
            'u' => $this->user->display_name,
            't' => $this->user->connect_token,
            'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
        ])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'You must have an active claim on this game to perform this action.',
            ]);

        Storage::disk('media')->assertCount('Badge/', 0);
        Storage::disk('s3')->assertCount('Badge/', 0);
    });

    test('cannot be uploaded by a regular user', function () {
        $this->post('doupload.php?r=uploadbadgeimage', [
            'u' => $this->user->display_name,
            't' => $this->user->connect_token,
            'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
        ])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        Storage::disk('media')->assertCount('Badge/', 0);
        Storage::disk('s3')->assertCount('Badge/', 0);
    });

    test('can be uploaded by an artist', function () {
        $this->user->assignRole(Role::ARTIST);

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

    test('cannot be uploaded by a writer', function () {
        $this->user->assignRole(Role::WRITER);

        $this->post('doupload.php?r=uploadbadgeimage', [
            'u' => $this->user->display_name,
            't' => $this->user->connect_token,
            'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
        ])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
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
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
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
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
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
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        Storage::disk('media')->assertCount('Badge/', 0);
        Storage::disk('s3')->assertCount('Badge/', 0);
    });

    test('can be uploaded without r parameter', function () {
        $this->user->assignRole(Role::DEVELOPER);

        // if r= is not provided, it is derived from the filename
        $this->post('doupload.php', [
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

    test('can only be uploaded 1500x per day', function () {
        $this->user->assignRole(Role::DEVELOPER);

        RateLimiter::increment('badge-upload:' . $this->user->id, amount: 1500);

        $this->post('doupload.php?r=uploadbadgeimage', [
            'u' => $this->user->display_name,
            't' => $this->user->connect_token,
            'file' => UploadedFile::fake()->image('uploadbadgeimage.png', 64, 64),
        ])
            ->assertStatus(429)
            ->assertExactJson([
                'Success' => false,
                'Status' => 429,
                'Code' => 'too_many_requests',
                'Error' => 'Too many requests. Please try again later.',
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
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Error' => 'Invalid file type.',
            ]);

        Storage::disk('media')->assertCount('Badge/', 0);
    });
});
