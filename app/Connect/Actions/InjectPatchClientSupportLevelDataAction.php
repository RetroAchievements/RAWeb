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

class InjectPatchClientSupportLevelDataAction
{
    /**
     * @param array $constructedPatchData Return value of BuildClientPatchDataAction::execute()
     * @param ClientSupportLevel $clientSupportLevel The current support level of the user's client/emulator
     * @param GameHash|null $gameHash The game hash patch data was possibly built for
     * @param Game|null $game The game patch data was possibly built for
     */
    public function execute(
        array $constructedPatchData,
        ClientSupportLevel $clientSupportLevel,
        ?GameHash $gameHash = null,
        ?Game $game = null,
    ): array {
        if (!$gameHash && !$game) {
            throw new InvalidArgumentException('Either gameHash or game must be provided to return a patch data response.');
        }

        $coreGame = $gameHash->game ?? $game;
        $canAddWarningAchievement = $coreGame->achievements_published > 0;

        if ($clientSupportLevel !== ClientSupportLevel::Full && $canAddWarningAchievement) {
            // We intentionally place the warning achievement at the top of the list.
            $constructedPatchData['PatchData']['Achievements'] = [
                $this->buildClientSupportWarningAchievement($clientSupportLevel),
                ...$constructedPatchData['PatchData']['Achievements'],
            ];
        }

        if ($clientSupportLevel === ClientSupportLevel::Unknown) {
            $constructedPatchData['Warning'] = 'The server does not recognize this client and will not allow hardcore unlocks. Please send a message to RAdmin on the RetroAchievements website for information on how to submit your emulator for hardcore consideration.';
        }

        return $constructedPatchData;
    }

    /**
     * This warning achievement should appear at the top of the emulator's achievements
     * list. It should automatically unlock after a few seconds of patch data retrieval.
     * The intention is to notify a user that they are using an outdated client
     * and need to update, as well as what the repercussions of their continued
     * play session with their current client might be.
     */
    private function buildClientSupportWarningAchievement(ClientSupportLevel $clientSupportLevel): array
    {
        return [
            'ID' => Achievement::CLIENT_WARNING_ID,
            'MemAddr' => '1=1.300.', // pop after 5 seconds
            'Title' => match ($clientSupportLevel) {
                ClientSupportLevel::Outdated => 'Warning: Outdated Emulator (please update)',
                ClientSupportLevel::Unsupported => 'Warning: Unsupported Emulator',
                default => 'Warning: Unknown Emulator',
            },
            //            'Description' => 'Hardcore unlocks cannot be earned using this emulator.',
            'Description' => ($clientSupportLevel === ClientSupportLevel::Outdated) ?
                'Starting in April, all unlocks from this emulator version will be softcore' :
                'Starting in April, all unlocks from this emulator will be softcore',
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
