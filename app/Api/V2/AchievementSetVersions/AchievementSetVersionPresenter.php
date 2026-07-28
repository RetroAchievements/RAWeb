<?php

namespace App\Api\V2\AchievementSetVersions;

use App\Models\AchievementSetVersion;

class AchievementSetVersionPresenter
{
    public function __construct(
        private readonly AchievementSetVersion $version,
    ) {
    }

    /**
     * @return array<int, array{id: int, points: int, isPromoted: bool, type: string|null}>|null
     */
    public function definition(): ?array
    {
        $definition = $this->version->definition;

        if ($definition === null) {
            return [];
        }

        $version = $definition['version'];

        switch ($version) {
            case 1:
                $parsed = [];
                foreach ($definition['achievements'] as $achievement) {
                    $parsed[] = [
                        'id' => $achievement['id'],
                        'points' => $achievement['points'],
                        'isPromoted' => $achievement['is_promoted'],
                        'type' => $achievement['type'],
                    ];
                }

                return $parsed;

            default:
                return null;
        }
    }
}
