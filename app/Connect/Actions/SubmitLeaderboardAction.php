<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Community\Enums\ArticleType;
use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\GeneratesLegacyAuditComment;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Actions\UpsertTriggerVersionAction;
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

    public function execute(User $user, 
        ?int $leaderboardId, ?int $gameId, ?int $achievementSetId,
        string $title, string $description,
        string $startTrigger, string $submitTrigger, string $cancelTrigger,
        string $valueDefinition, bool $lowerIsBetter, string $format): array
    {
        if (!$leaderboardId && !$gameId && !$achievemnetSetId) {
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
        if (!$request->has(['n','d','s','b','c','l','w','f','h'])) {
            return $this->missingParameters();
        }

        $this->leaderboardId = request()->integer('i', 0);
        $this->gameId = request()->integer('g', 0);
        $this->achievementSetId = request()->integer('p', 0);

        $this->title = request()->input('n') ?? '';
        $this->description = request()->input('d') ?? '';
        $this->startTrigger = request()->input('s') ?? '';
        $this->submitTrigger = request()->input('b') ?? '';
        $this->cancelTrigger = request()->input('c') ?? '';
        $this->valueDefinition = request()->input('l') ?? '';
        $this->lowerIsBetter = request()->boolean('w', false);
        $this->format = request()->input('f') ?? '';

        $checksum = request()->input('h') ?? '';
        if (!$this->checksumMatches($checksum, $this->user->display_name)) {
            if ($this->user->User === $this->user->display_name || !$this->checksumMatches($checksum, $this->user->User)) {
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

        return (strcasecmp($md5, $checksum) === 0);
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
            return $this->accessDenied();
        }

        $fields = [];

        if ($leaderboard->Title !== $this->title) {
            $leaderboard->Title = $this->title;
            $fields[] = 'title';
        }

        if ($leaderboard->Description !== $this->description) {
            $leaderboard->Description = $this->description;
            $fields[] = 'description';
        }

        if (!ValueFormat::isValid($this->format)) {
            return $this->invalidParameter('Unknown format: ' . $this->format);
        }
        if ($leaderboard->Format !== $this->format) {
            $leaderboard->Format = $this->format;
            $fields[] = 'format';
        }

        // Leaderboard::LowerIsBetter is not yet being cast to boolean. Use non-strict comparison
        if ($leaderboard->LowerIsBetter != $this->lowerIsBetter) {
            $leaderboard->LowerIsBetter = $this->lowerIsBetter;
            $fields[] = 'order';
        }

        $newMem = $this->buildMemString();
        if ($leaderboard->Mem != $newMem) {
            $leaderboard->Mem = $newMem;
            $fields[] = 'logic';
        }

        if ($leaderboard->isDirty()) {
            $leaderboard->save();

            if (in_array('logic', $fields)) {
                (new UpsertTriggerVersionAction())->execute(
                    $leaderboard,
                    $newMem,
                    versioned: true, // we don't currently support unpublished leaderboards
                    user: $this->user,
                );
            }

            $editString = implode(', ', $fields);
            if (!empty($editString)) {
                $this->addLegacyAuditComment(ArticleType::Leaderboard, $leaderboard->id,
                    "{$this->user->display_name} edited this leaderboard's $editString."
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
            $gameAchievementSet = GameAchievementSet::find($this->achievementSetId);
            if (!$gameAchievementSet) {
                return $this->resourceNotFound('achievement set');
            }

            $this->gameId = $gameAchievementSet->game_id;
        }
        else if (VirtualGameIdService::isVirtualGameId($this->gameId)) {
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
        if (!hasSetClaimed($this->user, $game->ID, false)) {
            return $this->accessDenied('You must have an active claim on this game to perform this action.');
        }

        if (!ValueFormat::isValid($this->format)) {
            return $this->invalidParameter('Unknown format: ' . $this->format);
        }

        $maxDisplayOrder = Leaderboard::where('GameID')->max('DisplayOrder') ?? 0;

        $leaderboard = Leaderboard::create([
            'GameID' => $this->gameId,
            'author_id' => $this->user->id,
            'DisplayOrder' => $maxDisplayOrder + 1,
            'Title' => $this->title,
            'Description' => $this->description,
            'Mem' => $this->buildMemString(),
            'LowerIsBetter' => $this->lowerIsBetter,
            'Format' => $this->format,
        ]);

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
