<?php

declare(strict_types=1);

use App\Support\Media\CreateDoubledScreenshotAction;

it('doubles the width while preserving the height', function () {
    // ARRANGE
    $source = imagecreatetruecolor(160, 228);
    ob_start();
    imagepng($source);
    $imageContents = ob_get_clean();

    // ACT
    $tempPath = (new CreateDoubledScreenshotAction())->execute($imageContents);

    // ASSERT
    expect($tempPath)->not->toBeNull();

    [$width, $height] = getimagesize($tempPath);
    expect($width)->toEqual(320);
    expect($height)->toEqual(228);

    @unlink($tempPath);
});

it('outputs a valid PNG file', function () {
    // ARRANGE
    $source = imagecreatetruecolor(160, 228);
    ob_start();
    imagepng($source);
    $imageContents = ob_get_clean();

    // ACT
    $tempPath = (new CreateDoubledScreenshotAction())->execute($imageContents);

    // ASSERT
    $info = getimagesize($tempPath);
    expect($info[2])->toEqual(IMAGETYPE_PNG);

    @unlink($tempPath);
});

it('does not leave an orphaned tempnam file after the returned temp file is removed', function () {
    // ARRANGE
    $source = imagecreatetruecolor(160, 228);
    ob_start();
    imagepng($source);
    $imageContents = ob_get_clean();

    $before = glob(sys_get_temp_dir() . '/doubled_screenshot_*') ?: [];
    $tempPath = null;
    $newFiles = [];

    // ACT
    try {
        $tempPath = (new CreateDoubledScreenshotAction())->execute($imageContents);

        expect($tempPath)->toBeFile();

        @unlink($tempPath);

        $after = glob(sys_get_temp_dir() . '/doubled_screenshot_*') ?: [];
        $newFiles = array_values(array_diff($after, $before));
    } finally {
        foreach ($newFiles as $newFile) {
            @unlink($newFile);
        }
    }

    // ASSERT
    expect($newFiles)->toBeEmpty();
});

it('throws for invalid image data', function () {
    (new CreateDoubledScreenshotAction())->execute('not an image');
})->throws(ErrorException::class);
