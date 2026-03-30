<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Community\Enums\CommentableType;
use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\GeneratesLegacyAuditComment;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\User;
use App\Platform\Actions\SyncEventAchievementMetadataAction;
use App\Platform\Actions\UpsertTriggerVersionAction;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementPoints;
use App\Platform\Enums\AchievementType;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

class SubmitAchievementAction extends BaseAuthenticatedApiAction
{
    use GeneratesLegacyAuditComment;

    protected int $achievementId;
    protected int $gameId;
    protected int $achievementSetId;
    protected string $title;
    protected string $description;
    protected string $badgeName;
    protected string $triggerDefinition;
    protected int $points;
    protected bool $isPromoted;
    protected ?string $type;
    protected string $format;

    public function execute(User $user,
        ?int $achievementId, ?int $gameId, ?int $achievementSetId,
        string $title, string $description, string $badgeName,
        string $triggerDefinition, int $points, ?string $type,
        bool $isPromoted): array
    {
        if (!$achievementId && !$gameId && !$achievementSetId) {
            return $this->missingParameters();
        }

        $this->achievementId = $achievementId ?? 0;
        $this->gameId = $gameId ?? 0;
        $this->achievementSetId = $achievementSetId ?? 0;
        $this->title = $title;
        $this->description = $description;
        $this->badgeName = $badgeName;
        $this->triggerDefinition = $triggerDefinition;
        $this->points = $points;
        $this->isPromoted = $isPromoted;
        $this->type = $type;
        $this->typeProvided = true;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has('a')) { // if an existing achievement ID is not provided
            if (!$request->has('g') && !$request->has('s')) { // either game or set is required
                return $this->missingParameters();
            }
        }

        // all properties (except type) must be provided regardless of update/create
        if (!$request->has(['n', 'd', 'b', 'm', 'z', 'f'])) {
            return $this->missingParameters();
        }

        $this->achievementId = $request->integer('a', 0);
        $this->gameId = $request->integer('g', 0);
        $this->achievementSetId = $request->integer('s', 0);

        $this->title = $request->input('n') ?? '';
        $this->description = $request->input('d') ?? '';
        $this->badgeName = $request->input('b') ?? '';
        $this->triggerDefinition = $request->input('m') ?? '';
        $this->points = $request->integer('z', 0);
        if ($request->has('x')) {
            $this->type = $request->input('x');
        } else {
            $this->type = $this->achievementId ? 'not-given' : null;
        }

        $flag = $request->input('f');
        if ($flag === '3') {
            $this->isPromoted = true;
        } elseif ($flag === '5') {
            $this->isPromoted = false;
        } else {
            return $this->invalidParameter('Unknown flag: ' . $flag);
        }

        $checksum = $request->input('h') ?? '';
        if (!$this->checksumMatches($checksum, $this->user->display_name)) {
            if ($this->user->username === $this->user->display_name || !$this->checksumMatches($checksum, $this->user->username)) {
                return $this->accessDenied('Invalid checksum.');
            }
        }

        return null;
    }

    private function checksumMatches(string $checksum, string $username): bool
    {
        if (!$checksum) {
            return false;
        }

        $scaledPoints = $this->points * 3;
        $message = "{$username}SECRET{$this->achievementId}SEC{$this->triggerDefinition}{$this->points}RE2{$scaledPoints}";
        $md5 = md5($message);

        return strcasecmp($md5, $checksum) === 0;
    }

    protected function process(): array
    {
        if (!$this->achievementId) {
            return $this->createAchievement();
        }

        return $this->updateAchievement();
    }

    private function updateAchievement(): array
    {
        if ($this->achievementId >= Achievement::CLIENT_WARNING_ID) {
            return $this->invalidParameter('Cannot modify warning achievement.');
        }

        $achievement = Achievement::find($this->achievementId);
        if (!$achievement) {
            return $this->resourceNotFound('achievement');
        }

        // Check if user has permission to update the achievement.
        if (!$this->user->can('update', $achievement)) {
            // Special logic only applies to junior developers.
            // Anyone else who failed the update check should be bounced immediately.
            if (!$this->user->hasRole(Role::DEVELOPER_JUNIOR)) {
                return $this->mustBeDeveloper();
            }

            // A junior developer must be the achievement author.
            if ($this->user->id !== $achievement->user_id) {
                return $this->mustBeDeveloper();
            }

            // A junior developer must have a claim on the game.
            if (!$this->user->hasActiveClaimOnGameId($achievement->game_id)) {
                return $this->mustHaveClaim();
            }

            // A junior developer is not allowed to modify logic of promoted achievements.
            if ($this->isPromoted && $this->triggerDefinition !== $achievement->trigger_definition) {
                return $this->mustBeDeveloper();
            }
        }

        $fields = [];

        if ($achievement->points !== $this->points) {
            if (!AchievementPoints::isValid($this->points)) {
                return $this->invalidParameter("Invalid points value: $this->points");
            }

            $achievement->points = $this->points;
            $fields[] = 'points';
        }

        if ($achievement->image_name !== $this->badgeName) {
            $achievement->image_name = $this->badgeName;
            $fields[] = 'badge';
        }

        if ($achievement->title !== $this->title) {
            $achievement->title = $this->title;
            $fields[] = 'title';
        }

        if ($achievement->description !== $this->description) {
            $achievement->description = $this->description;
            $fields[] = 'description';
        }

        $recalculateBeatTimes = false;
        if ($this->type !== 'not-given' && $achievement->type !== $this->type) {
            if ($this->type && !AchievementType::isValid($this->type)) {
                return $this->invalidParameter('Unknown type: ' . $this->type);
            }

            if (AchievementType::isProgression($this->type) && !$achievement->game->getCanHaveBeatenTypes()) {
                return $this->invalidParameter('Cannot set progression or win condition type on achievement in subset, test kit, or event.');
            }

            // if changing to/from Progression/WinCondition, recalculate all beat times
            $recalculateBeatTimes = AchievementType::isProgression($this->type) || AchievementType::isProgression($achievement->type);

            $achievement->type = $this->type;
            $fields[] = 'type';
        }

        if ($achievement->trigger_definition != $this->triggerDefinition) {
            $achievement->trigger_definition = $this->triggerDefinition;
            $fields[] = 'logic';
        }

        $changingPromotedStatus = $achievement->is_promoted !== $this->isPromoted;
        if ($changingPromotedStatus) {
            $achievement->is_promoted = $this->isPromoted;

            // junior developers are not allowed to promote/demote achievements
            if ($this->user->hasRole(Role::DEVELOPER_JUNIOR)) {
                return $this->mustBeDeveloper();
            }

            if (!isValidConsoleId($achievement->game->system_id)) {
                return $this->accessDenied("You cannot promote achievements for a game from an unsupported console (console ID: {$achievement->game->system_id}).");
            }
        }

        if ($achievement->isDirty()) {
            // if any event achievements are attached to this achievement, update them too
            // this relies on the dirty state of the achievement, so must be called before save()
            (new SyncEventAchievementMetadataAction())->execute($achievement);

            $achievement->save();

            if (in_array('logic', $fields)) {
                $achievement->ensureAuthorshipCredit($this->user, AchievementAuthorTask::Logic);

                (new UpsertTriggerVersionAction())->execute(
                    $achievement,
                    $this->triggerDefinition,
                    versioned: $achievement->is_promoted,
                    user: $this->user,
                );
            }

            if ($changingPromotedStatus) {
                if ($this->isPromoted) {
                    $this->addLegacyAuditComment(CommentableType::Achievement, $achievement->id,
                        "{$this->user->display_name} promoted this achievement."
                    );
                } else {
                    $this->addLegacyAuditComment(CommentableType::Achievement, $achievement->id,
                        "{$this->user->display_name} demoted this achievement."
                    );
                }

                expireGameTopAchievers($achievement->game_id);

                // if promoting/demoting a progression achievement, we need to recalculate beat times
                $recalculateBeatTimes |= AchievementType::isProgression($achievement->type);
            } else {
                $editString = implode(', ', $fields);
                if (!empty($editString)) {
                    $this->addLegacyAuditComment(CommentableType::Achievement, $achievement->id,
                        "{$this->user->display_name} edited this achievement's $editString."
                    );
                }
            }

            static_setlastupdatedgame($achievement->game_id);
            static_setlastupdatedachievement($achievement->id);

            if ($recalculateBeatTimes) {
                // changing the type of an achievement or promoting/demoting it can affect
                // the time to beat a game. recalculate them for anyone who has beaten the game.
                $affectedUserIds = PlayerGame::query()
                    ->where('game_id', $achievement->game_id)
                    ->whereNotNull('beaten_at')
                    ->select(['user_id'])
                    ->pluck('user_id');
                foreach ($affectedUserIds as $userId) {
                    dispatch(new UpdatePlayerGameMetricsJob($userId, $achievement->game_id));
                }
            }
        }

        return [
            'Success' => true,
            'AchievementID' => $achievement->id,
        ];
    }

    private function createAchievement(): array
    {
        if ($this->achievementSetId) {
            $gameAchievementSet = GameAchievementSet::find($this->achievementSetId);
            if (!$gameAchievementSet) {
                return $this->resourceNotFound('achievement set');
            }

            $this->gameId = $gameAchievementSet->game_id;
        } elseif (VirtualGameIdService::isVirtualGameId($this->gameId)) {
            [$this->gameId, $compatibility] = VirtualGameIdService::decodeVirtualGameId($this->gameId);
        }

        $game = Game::find($this->gameId);
        if (!$game) {
            return $this->gameNotFound();
        }

        // Check if user has permission to create an achievement.
        if (!$this->user->can('create', [Achievement::class, $game])) {
            return $this->mustBeDeveloper();
        }

        // Make sure the user has a claim on the game.
        if (!$this->user->hasActiveClaimOnGameId($game->id)) {
            return $this->mustHaveClaim();
        }

        if (!AchievementPoints::isValid($this->points)) {
            return $this->invalidParameter("Invalid points value: $this->points");
        }

        if ($this->type && !AchievementType::isValid($this->type)) {
            return $this->invalidParameter('Unknown type: ' . $this->type);
        }

        $achievement = Achievement::create([
            'game_id' => $this->gameId,
            'user_id' => $this->user->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_name' => $this->badgeName,
            'trigger_definition' => $this->triggerDefinition,
            'points' => $this->points,
            'is_promoted' => false, // new achievements are always created in an unpromoted state
            'type' => $this->type,
        ]);

        (new UpsertTriggerVersionAction())->execute(
            $achievement,
            $this->triggerDefinition,
            versioned: $achievement->is_promoted,
            user: $this->user,
        );

        $achievement->ensureAuthorshipCredit($this->user, AchievementAuthorTask::Logic);

        static_addnewachievement($achievement->id);

        $this->addLegacyAuditComment(CommentableType::Achievement, $achievement->id,
            "{$this->user->display_name} uploaded this achievement."
        );

        return [
            'Success' => true,
            'AchievementID' => $achievement->id,
        ];
    }

    private function mustBeDeveloper(): array
    {
        return $this->accessDenied('You must be a developer to perform this action! Please drop a message in the forums to apply.');
    }

    private function mustHaveClaim(): array
    {
        return $this->accessDenied('You must have an active claim on this game to perform this action.');
    }
}
