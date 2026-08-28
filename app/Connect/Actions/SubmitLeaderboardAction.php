<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Community\Enums\CommentableType;
use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\GeneratesLegacyAuditComment;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\User;
use App\Platform\Actions\UpsertTriggerVersionAction;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Enums\ValueFormat;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

class SubmitLeaderboardAction extends BaseAuthenticatedApiAction
{
    use GeneratesLegacyAuditComment;

    protected int $leaderboardId;
    protected int $gameId;
    protected int $achievementSetId;
    protected string $title;
    protected string $description;
    protected string $startTrigger;
    protected string $submitTrigger;
    protected string $cancelTrigger;
    protected string $valueDefinition;
    protected bool $lowerIsBetter;
    protected string $format;
    protected string $state;

    public function execute(User $user,
        ?int $leaderboardId, ?int $gameId, ?int $achievementSetId,
        string $title, string $description,
        string $startTrigger, string $submitTrigger, string $cancelTrigger,
        string $valueDefinition, bool $lowerIsBetter, string $format,
        LeaderboardState $state = LeaderboardState::Active): array
    {
        if (!$leaderboardId && !$gameId && !$achievementSetId) {
            return $this->missingParameters();
        }

        $this->leaderboardId = $leaderboardId ?? 0;
        $this->gameId = $gameId ?? 0;
        $this->achievementSetId = $achievementSetId ?? 0;
        $this->title = $title;
        $this->description = $description;
        $this->startTrigger = $startTrigger;
        $this->submitTrigger = $submitTrigger;
        $this->cancelTrigger = $cancelTrigger;
        $this->valueDefinition = $valueDefinition;
        $this->lowerIsBetter = $lowerIsBetter;
        $this->format = $format;
        $this->state = $state->value;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has('i')) { // if an existing leaderboard ID is not provided
            if (!$request->has('g') && !$request->has('p')) { // either game or set is required
                return $this->missingParameters();
            }
        }

        // all properties must be provided regardless of update/create
        if (!$request->has(['n', 'd', 's', 'b', 'c', 'l', 'w', 'f', 'h'])) {
            return $this->missingParameters();
        }

        $this->leaderboardId = $request->integer('i', 0);
        $this->gameId = $request->integer('g', 0);
        $this->achievementSetId = $request->integer('p', 0);

        $this->title = $request->input('n') ?? '';
        $this->description = $request->input('d') ?? '';
        $this->startTrigger = $request->input('s') ?? '';
        $this->submitTrigger = $request->input('b') ?? '';
        $this->cancelTrigger = $request->input('c') ?? '';
        $this->valueDefinition = $request->input('l') ?? '';
        $this->lowerIsBetter = $request->boolean('w', false);
        $this->format = $request->input('f') ?? '';
        $this->state = $request->input('m') ?? 'not-given';

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

        $message = "{$username}SECRET{$this->leaderboardId}SEC{$this->startTrigger}{$this->submitTrigger}{$this->cancelTrigger}{$this->valueDefinition}RE2{$this->format}";
        $md5 = md5($message);

        return strcasecmp($md5, $checksum) === 0;
    }

    protected function process(): array
    {
        if (!$this->leaderboardId) {
            return $this->createLeaderboard();
        }

        return $this->updateLeaderboard();
    }

    private function updateLeaderboard(): array
    {
        $leaderboard = Leaderboard::find($this->leaderboardId);
        if (!$leaderboard) {
            return $this->resourceNotFound('leaderboard');
        }

        // Check if user has permission to update the leaderboard.
        if (!$this->user->can('update', $leaderboard)) {
            return $this->mustBeDeveloper();
        }

        if (!ValueFormat::isValid($this->format)) {
            return $this->invalidParameter('Unknown format: ' . $this->format);
        }

        $newMem = $this->buildMemString();

        $changes = [];
        if ($leaderboard->title !== $this->title) {
            $changes['title'] = $this->title;
        }

        if ($leaderboard->description !== $this->description) {
            $changes['description'] = $this->description;
        }

        if ($leaderboard->format !== $this->format) {
            $changes['format'] = $this->format;
        }

        if ($leaderboard->rank_asc != $this->lowerIsBetter) {
            $changes['rank_asc'] = $this->lowerIsBetter;
        }

        if ($leaderboard->trigger_definition != $newMem) {
            $changes['trigger_definition'] = $newMem;
        }

        $stateChanged = false;
        if ($this->state === 'not-given') {
            $state = $leaderboard->state;
        } else {
            $s = LeaderboardState::tryFrom($this->state);
            if ($s === null) {
                return $this->invalidParameter('Unknown state: ' . $this->state);
            }
            $state = $s;

            if ($state !== $leaderboard->state) {
                // junior developers are not allowed to promote/demote leaderboards
                if ($this->user->hasRole(Role::DEVELOPER_JUNIOR)) {
                    return $this->mustBeDeveloper();
                }

                if (!isValidConsoleId($leaderboard->game->system_id)) {
                    return $this->accessDenied("You cannot promote leaderboards for a game from an unsupported console (console ID: {$leaderboard->game->system_id}).");
                }

                $leaderboard->state = $s;
                $stateChanged = true;
            } elseif ($state !== LeaderboardState::Unpromoted && $this->user->hasRole(Role::DEVELOPER_JUNIOR)) {
                // junior developers are not allowed to modify promoted leaderboards
                // TODO: this should be in LeaderboardPolicy::juniorDeveloperCanUpdate,
                //       but that has to be more lenient until the new DLL is available.
                return $this->mustBeDeveloper();
            }

        }

        $labels = [];
        foreach ($changes as $field => $value) {
            if (!$this->user->can('updateField', [$leaderboard, $field])) {
                return $this->accessDenied();
            }

            $leaderboard->{$field} = $value;

            $labels[] = match ($field) {
                'rank_asc' => 'order',
                'trigger_definition' => 'logic',
                default => $field,
            };
        }

        if ($leaderboard->isDirty()) {
            $leaderboard->save();

            if (isset($changes['trigger_definition'])) {
                (new UpsertTriggerVersionAction())->execute(
                    $leaderboard,
                    $newMem,
                    versioned: ($state !== LeaderboardState::Unpromoted),
                    user: $this->user,
                );
            }

            // TODO: remove legacy comments when leaderboard page can display changes from audit_log
            if (!empty($labels)) {
                $this->addLegacyAuditComment(CommentableType::Leaderboard, $leaderboard->id,
                    "{$this->user->display_name} edited this leaderboard's " . implode(', ', $labels) . '.'
                );
            }

            if ($stateChanged) {
                $action = match ($leaderboard->state) {
                    LeaderboardState::Active => 'promoted',
                    LeaderboardState::Unpromoted => 'demoted',
                    LeaderboardState::Disabled => 'disabled',
                };

                $this->addLegacyAuditComment(CommentableType::Leaderboard, $leaderboard->id,
                    "{$this->user->display_name} $action this leaderboard."
                );
            }
        }

        return [
            'Success' => true,
            'LeaderboardID' => $leaderboard->id,
        ];
    }

    private function createLeaderboard(): array
    {
        if ($this->achievementSetId) {
            $gameAchievementSet = GameAchievementSet::core()->where('achievement_set_id', $this->achievementSetId)->first();
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

        // Check if user has permission to create a leaderboard.
        if (!$this->user->can('create', [Leaderboard::class, $game])) {
            return $this->accessDenied();
        }

        // Make sure the user has a claim on the game.
        if (!hasSetClaimed($this->user, $game->id, false)) {
            return $this->mustHaveActiveClaim();
        }

        if (!ValueFormat::isValid($this->format)) {
            return $this->invalidParameter('Unknown format: ' . $this->format);
        }

        if ($this->state === 'not-given') {
            // legacy clients expect to create active leaderboards without specifying a state
            $state = LeaderboardState::Active;
        } else {
            $s = LeaderboardState::tryFrom($this->state);
            if ($s === null) {
                return $this->invalidParameter('Unknown state: ' . $this->state);
            }
            $state = $s;
        }

        $maxOrderColumn = Leaderboard::where('game_id', $game->id)->max('order_column') ?? 0;

        $newMem = $this->buildMemString();
        $leaderboard = Leaderboard::create([
            'game_id' => $this->gameId,
            'author_id' => $this->user->id,
            'order_column' => $maxOrderColumn + 1,
            'title' => $this->title,
            'description' => $this->description,
            'trigger_definition' => $newMem,
            'rank_asc' => $this->lowerIsBetter,
            'format' => $this->format,
            'state' => $state,
        ]);

        (new UpsertTriggerVersionAction())->execute(
            $leaderboard,
            $newMem,
            versioned: ($state !== LeaderboardState::Unpromoted),
            user: $this->user,
        );

        return [
            'Success' => true,
            'LeaderboardID' => $leaderboard->id,
        ];
    }

    private function buildMemString(): string
    {
        return "STA:{$this->startTrigger}::CAN:{$this->cancelTrigger}::SUB:{$this->submitTrigger}::VAL:{$this->valueDefinition}";
    }
}
