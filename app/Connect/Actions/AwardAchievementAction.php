<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\CanBeDelegated;
use App\Connect\Support\GeneratesConnectWarnings;
use App\Enums\ClientSupportLevel;
use App\Models\Achievement;
use App\Models\GameHash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AwardAchievementAction extends BaseAuthenticatedApiAction
{
    use CanBeDelegated;
    use GeneratesConnectWarnings;

    protected Achievement $achievement;
    protected bool $hardcore;
    protected ?GameHash $gameHash = null;
    protected Carbon $when;

    public function execute(
        User $user,
        Achievement $achievement,
        bool $hardcore,
        ?GameHash $gameHash = null,
        ?Carbon $when = null,
    ): array {
        $this->user = $user;
        $this->achievement = $achievement;
        $this->hardcore = $hardcore;
        $this->gameHash = $gameHash;
        $this->when = $when ?? Carbon::now();
        $this->clientSupportLevel = ClientSupportLevel::Full;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['a'])) {
            return $this->missingParameters();
        }

        // Ignore warning achievement
        $achievementId = $request->integer('a', 0);
        if ($achievementId === Achievement::CLIENT_WARNING_ID) {
            return [
                'Success' => true,
                'AchievementID' => $achievementId,
                'AchievementsRemaining' => 9999,
                'Score' => $this->user->points_hardcore,
                'SoftcoreScore' => $this->user->points,
            ];
        }

        // Ensure achievement exists
        $achievement = Achievement::query()
            ->where('id', $achievementId)
            ->with('game')
            ->first();
        if (!$achievement) {
            return $this->resourceNotFound('achievement');
        }
        $this->achievement = $achievement;

        // Determine if request is being delegated
        $actingUser = $this->user;
        $result = $this->applyDelegationForUnlock($request, $this->achievement);
        if ($result !== null) {
            return $result;
        }

        // Ignore negative values and offsets greater than max.
        // Clamping offset will invalidate validationHash.
        $maxOffset = 14 * 24 * 60 * 60; // 14 days
        $offset = min(max((int) $request->input('o', 0), 0), $maxOffset);

        $this->hardcore = $request->boolean('h', false);

        // Check validation hash (note use of parameters k/u - the parameter may not have the same casing as the model)
        $validationHash = strtolower($request->input('v', ''));

        if ($this->user != $actingUser) {
            // Delegated unlocks will be rejected if the appropriate validation hash is not provided
            // NOTE: delegated validationStr has an extra copy of the achievement ID.
            $validationStr = $this->achievement->id . $request->input('k', '') . ($this->hardcore ? '1' : '0') . $this->achievement->id;
            if ($offset !== 0) {
                $validationStr .= $offset;
            }
            if ($validationHash !== md5($validationStr)) {
                return $this->accessDenied();
            }
        } elseif ($offset !== 0) {
            // NOTE: the achievement ID appears before the offset
            $validationStr = $this->achievement->id . $request->input('u', '') . ($this->hardcore ? '1' : '0') . $this->achievement->id . $offset;
            if ($validationHash !== md5($validationStr)) {
                $this->addSmell($request, empty($validationHash) ? 'no_validation' : 'bad_validation');

                // hash failed - ignore offset
                $offset = 0;
            }
        } else {
            // An offset of 0 is expected to not be included in the hash. But if the first
            // check fails, also check to see if an offset of 0 was included and ignore it.
            $validationStr = $this->achievement->id . $request->input('u', '') . ($this->hardcore ? '1' : '0');
            if ($validationHash !== md5($validationStr)
                && $validationHash !== md5("{$validationStr}{$this->achievement->id}0")) {
                $this->addSmell($request, empty($validationHash) ? 'no_validation' : 'bad_validation');
            }
        }

        // Check client support level
        $this->validateClient($request, $this->achievement->game);

        if ($this->hardcore && !$this->clientSupportLevel->allowsHardcoreUnlocks()) {
            $this->hardcore = false;
        }

        // Capture game hash (if provided)
        $gameHashMD5 = $request->input('m', '');
        if ($gameHashMD5) {
            $this->gameHash = GameHash::whereMd5($gameHashMD5)->first();
        }

        // If a smell was detected, flesh out the warning
        if ($this->connectWarning) {
            $this->connectWarning->related_type = 'achievement';
            $this->connectWarning->related_id = $this->achievement->id;
            $this->connectWarning->hardcore = (int) $this->hardcore;
            $this->connectWarning->offset = $request->has('o') ? (int) $request->input('o') : null;
            $this->connectWarning->validation_hash = mb_strimwidth($request->input('v', ''), 0, 40, "..."); // capture unnormalized parameter
        }

        if ($this->clientSupportLevel === ClientSupportLevel::Blocked) {
            return $this->unsupportedClient();
        }

        $this->when = Carbon::now()->subSeconds($offset);

        return null;
    }

    protected function process(): array
    {
        $action = new AwardAchievementsAction();
        $result = $action->execute(
            $this->user,
            [$this->achievement],
            $this->hardcore,
            $this->gameHash,
            $this->when,
            $this->userAgent,
            $this->ipAddress,
        );

        if ($this->connectWarning) {
            $this->connectWarning->player_session_id = $action->playerSession?->id;
        }

        if (!$result['Success']) {
            return $result;
        }

        $retVal = [
            'Success' => true,
            'AchievementID' => $this->achievement->id,
            'AchievementsRemaining' => $result['AchievementsRemaining'],
            'Score' => $result['Score'],
            'SoftcoreScore' => $result['SoftcoreScore'],
        ];

        if (in_array($this->achievement->id, $result['ExistingIDs'])) {
            // The achievement was previously unlocked. Set the error message to indicate such.

            // =============================================================================
            // ===== DO NOT CHANGE THESE MESSAGES ==========================================
            // The client detects the "User already has" and does not report them as errors.
            if ($this->hardcore) {
                $retVal['Error'] = "User already has this achievement unlocked in hardcore mode.";
            } else {
                $retVal['Error'] = "User already has this achievement unlocked.";
            }
            // =============================================================================

            // If this didn't cascade to any other achievements (i.e. events), set Success to false
            if (empty($result['SuccessfulIDs'])) {
                $retVal['Success'] = false;
            }
        }

        return $retVal;
    }
}
