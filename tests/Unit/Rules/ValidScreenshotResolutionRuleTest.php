<?php

declare(strict_types=1);

use App\Models\System;
use App\Rules\ValidScreenshotResolutionRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

it('passes for an exact base resolution match', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => [['width' => 256, 'height' => 224]],
        'has_analog_tv_output' => false,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 256, 224);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeFalse();
});

it('passes for a 2x integer multiple', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => [['width' => 160, 'height' => 144]],
        'has_analog_tv_output' => false,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 320, 288);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeFalse();
});

it('passes for a 3x integer multiple', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => [['width' => 160, 'height' => 144]],
        'has_analog_tv_output' => false,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 480, 432);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeFalse();
});

it('rejects a 4x integer multiple', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => [['width' => 160, 'height' => 144]],
        'has_analog_tv_output' => false,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 640, 576);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeTrue();
});

it('rejects non-integer scaling', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => [['width' => 256, 'height' => 224]],
        'has_analog_tv_output' => false,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 584, 448);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeTrue();
});

it('passes for an SMPTE 601 NTSC resolution when the system has analog TV output', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => [['width' => 320, 'height' => 224]],
        'has_analog_tv_output' => true,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 720, 480);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeFalse();
});

it('rejects an SMPTE 601 resolution when the system has no analog TV output', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => [['width' => 160, 'height' => 144]],
        'has_analog_tv_output' => false,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 720, 480);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeTrue();
});

it('passes any dimensions when the system has null resolutions', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => null,
        'has_analog_tv_output' => false,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 800, 600);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeFalse();
});

it('passes when the image matches the second base resolution', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => [['width' => 256, 'height' => 224], ['width' => 256, 'height' => 240]],
        'has_analog_tv_output' => false,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 256, 240);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeFalse();
});

it('rejects scaling that only matches on one axis', function () {
    // ARRANGE
    $system = System::factory()->make([
        'screenshot_resolutions' => [['width' => 256, 'height' => 224]],
        'has_analog_tv_output' => false,
    ]);
    // 512x224 is 2x width but 1x height — asymmetric scaling.
    $file = UploadedFile::fake()->image('screenshot.png', 512, 224);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    expect($validator->fails())->toBeTrue();
});

it('includes the system name and expected resolutions in the error message', function () {
    // ARRANGE
    $system = System::factory()->make([
        'name' => 'NES/Famicom',
        'screenshot_resolutions' => [['width' => 256, 'height' => 224], ['width' => 256, 'height' => 240]],
        'has_analog_tv_output' => true,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 584, 448);

    // ACT
    $validator = Validator::make(
        ['screenshot' => $file],
        ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
    );

    // ASSERT
    $errorMessage = $validator->errors()->first('screenshot');
    expect($errorMessage)
        ->toContain('584x448')
        ->toContain('NES/Famicom')
        ->toContain('256x224, 256x240')
        ->toContain('SMPTE 601');
});
