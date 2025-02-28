<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Achievement;
use App\Models\Emulator;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('CreateAchievementTicketPageProps')]
class CreateAchievementTicketPagePropsData extends Data
{
    public function __construct(
        public AchievementData $achievement,
        /** @var Collection<int, EmulatorData> */
        public Collection $emulators,
        /** @var GameHashData[] */
        public array $gameHashes,
        public ?string $selectedEmulator = null,
        public ?int $selectedGameHashId = null,
        public ?string $emulatorVersion = null,
        public ?string $emulatorCore = null,
        public ?int $selectedMode = null,
    ) {
    }

    public static function fromAchievement(Achievement $achievement): self
    {
        $emulators = Emulator::query()
            ->forSystem($achievement->game->system->id)
            ->active()
            ->get()
            ->map(fn (Emulator $emulator) => EmulatorData::fromEmulator($emulator));

        $gameHashes = GameHashData::fromCollection($achievement->game->hashes);

        return new self(
            achievement: AchievementData::fromAchievement($achievement)->include(
                'game',
                'game.system'
            ),
            emulators: $emulators,
            gameHashes: $gameHashes,
        );
    }
}
