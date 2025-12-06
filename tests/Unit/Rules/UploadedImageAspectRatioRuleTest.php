<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\UploadedImageAspectRatioRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UploadedImageAspectRatioRuleTest extends TestCase
{
    public function testItPassesWithExact32To9AspectRatio(): void
    {
        // Arrange
        // ... 1920x540 = exactly 32:9 ...
        $file = UploadedFile::fake()->image('banner.png', 1920, 540);

        // Act
        $validator = Validator::make(['banner' => $file], [
            'banner' => [new UploadedImageAspectRatioRule(32 / 9, 0.05)],
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function testItPassesWithin5PercentTolerance(): void
    {
        // Arrange
        // ... 1920x520 is slightly off from 32:9 but within a 5% tolerance ...
        $file = UploadedFile::fake()->image('banner.png', 1920, 520);

        // Act
        $validator = Validator::make(['banner' => $file], [
            'banner' => [new UploadedImageAspectRatioRule(32 / 9, 0.05)],
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function testItPassesWithHigherResolution32To9Image(): void
    {
        // Arrange
        // ... 3200x900 = exactly 32:9 at higher resolution ...
        $file = UploadedFile::fake()->image('banner.png', 3200, 900);

        $validator = Validator::make(['banner' => $file], [
            'banner' => [new UploadedImageAspectRatioRule(32 / 9, 0.05)],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItFailsWith16To9AspectRatio(): void
    {
        // Arrange
        // ... 1920x1080 = 16:9, which is outside 32:9's ±5% tolerance ...
        $file = UploadedFile::fake()->image('banner.png', 1920, 1080);

        // Act
        $validator = Validator::make(['banner' => $file], [
            'banner' => [new UploadedImageAspectRatioRule(32 / 9, 0.05)],
        ]);

        // Assert
        $this->assertTrue($validator->fails());
    }

    public function testItFailsWithSquareAspectRatio(): void
    {
        // Arrange
        // ... 1000x1000 = 1:1, far outside 32:9's ±5% tolerance ...
        $file = UploadedFile::fake()->image('banner.png', 1000, 1000);

        // Act
        $validator = Validator::make(['banner' => $file], [
            'banner' => [new UploadedImageAspectRatioRule(32 / 9, 0.05)],
        ]);

        // Assert
        $this->assertTrue($validator->fails());
    }

    public function testItFailsWhenAspectRatioIsJustOutsideTolerance(): void
    {
        // Arrange
        // ... 1920x600 is just barely outside a 5% tolerance ...
        $file = UploadedFile::fake()->image('banner.png', 1920, 600);

        // Act
        $validator = Validator::make(['banner' => $file], [
            'banner' => [new UploadedImageAspectRatioRule(32 / 9, 0.05)],
        ]);

        // Assert
        $this->assertTrue($validator->fails());
    }

    public function testItWorksWithCustomTolerance(): void
    {
        // Arrange
        // ... 1920x600 fails with a 5% tolerance but passes with a 15% tolerance ...
        $file = UploadedFile::fake()->image('banner.png', 1920, 600);

        // Act
        $validator = Validator::make(['banner' => $file], [
            'banner' => [new UploadedImageAspectRatioRule(32 / 9, 0.15)],
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function testItFailsWithNonImageFile(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('document.pdf', 100);

        // Act
        $validator = Validator::make(['banner' => $file], [
            'banner' => [new UploadedImageAspectRatioRule(32 / 9, 0.05)],
        ]);

        // Assert
        $this->assertTrue($validator->fails());
    }
}
