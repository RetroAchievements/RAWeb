<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @extends Factory<GameScreenshot>
 */
class GameScreenshotFactory extends Factory
{
    protected $model = GameScreenshot::class;

    public function definition(): array
    {
        return [
            'game_id' => null,
            'media_id' => fn () => Media::create([
                'model_type' => Game::class,
                'model_id' => 0,
                'uuid' => $this->faker->uuid(),
                'collection_name' => 'screenshots',
                'name' => $this->faker->word(),
                'file_name' => $this->faker->word() . '.png',
                'mime_type' => 'image/png',
                'disk' => 's3',
                'size' => 1024,
                'manipulations' => [],
                'custom_properties' => ['sha1' => sha1($this->faker->unique()->word())],
                'generated_conversions' => [],
                'responsive_images' => [],
            ])->id,
            'type' => ScreenshotType::Ingame,
            'is_primary' => false,
            'status' => GameScreenshotStatus::Approved,
            'description' => null,
            'captured_by_user_id' => null,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    public function title(): static
    {
        return $this->state(fn () => ['type' => ScreenshotType::Title]);
    }

    public function ingame(): static
    {
        return $this->state(fn () => ['type' => ScreenshotType::Ingame]);
    }

    public function completion(): static
    {
        return $this->state(fn () => ['type' => ScreenshotType::Completion]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => GameScreenshotStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => GameScreenshotStatus::Rejected]);
    }
}
