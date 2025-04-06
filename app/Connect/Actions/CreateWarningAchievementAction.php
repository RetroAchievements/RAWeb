<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Enums\ClientSupportLevel;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Platform\Enums\AchievementFlag;
use Carbon\Carbon;
use InvalidArgumentException;

class CreateWarningAchievementAction
{
    public function execute(string $title, string $description): array {
        return [
            'ID' => Achievement::CLIENT_WARNING_ID,
            'MemAddr' => '1=1.300.', // pop after 5 seconds
            'Title' => $title,
            'Description' => $description,
            'Points' => 0,
            'Author' => '',
            'Modified' => Carbon::now()->unix(),
            'Created' => Carbon::now()->unix(),
            'BadgeName' => '00000',
            'Flags' => AchievementFlag::OfficialCore->value,
            'Type' => null,
            'Rarity' => 0.0,
            'RarityHardcore' => 0.0,
            'BadgeURL' => media_asset("Badge/00000.png"),
            'BadgeLockedURL' => media_asset("Badge/00000_lock.png"),
        ];
    }
}
