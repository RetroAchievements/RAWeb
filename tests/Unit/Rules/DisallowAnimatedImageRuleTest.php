<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\DisallowAnimatedImageRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DisallowAnimatedImageRuleTest extends TestCase
{
    public function testItPassesForStaticPng(): void
    {
        // Arrange
        $file = new UploadedFile(
            base_path('tests/Fixtures/static.png'),
            'static.png',
            'image/png',
            null,
            true
        );

        // Act
        $validator = Validator::make(['image' => $file], [
            'image' => [new DisallowAnimatedImageRule()],
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function testItPassesForStaticWebp(): void
    {
        // Arrange
        $file = new UploadedFile(
            base_path('tests/Fixtures/static.webp'),
            'static.webp',
            'image/webp',
            null,
            true
        );

        // Act
        $validator = Validator::make(['image' => $file], [
            'image' => [new DisallowAnimatedImageRule()],
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function testItPassesForJpeg(): void
    {
        // Arrange
        // ... JPEG doesn't support animation, so any JPEG should pass ...
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        // Act
        $validator = Validator::make(['image' => $file], [
            'image' => [new DisallowAnimatedImageRule()],
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function testItFailsForAnimatedPng(): void
    {
        // Arrange
        $file = new UploadedFile(
            base_path('tests/Fixtures/animated.png'),
            'animated.png',
            'image/png',
            null,
            true
        );

        // Act
        $validator = Validator::make(['image' => $file], [
            'image' => [new DisallowAnimatedImageRule()],
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString(
            'Animated images are not allowed',
            $validator->errors()->first('image')
        );
    }

    public function testItFailsForAnimatedWebp(): void
    {
        // Arrange
        $file = new UploadedFile(
            base_path('tests/Fixtures/animated.webp'),
            'animated.webp',
            'image/webp',
            null,
            true
        );

        // Act
        $validator = Validator::make(['image' => $file], [
            'image' => [new DisallowAnimatedImageRule()],
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString(
            'Animated images are not allowed',
            $validator->errors()->first('image')
        );
    }
}
