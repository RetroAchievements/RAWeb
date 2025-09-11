<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Community\Enums\ArticleType;
use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Models\Game;
use App\Models\User;
use App\Platform\Actions\UpsertTriggerVersionAction;
use Illuminate\Http\Request;

class SubmitRichPresenceAction extends BaseAuthenticatedApiAction
{
    protected int $gameId;
    protected string $richPresence;

    public function execute(int $gameId, string $richPresence, User $user): array
    {
        $this->gameId = $gameId;
        $this->richPresence = $richPresence;
        $this->user = $user;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['g', 'd'])) {
            return $this->missingParameters();
        }

        $this->gameId = request()->integer('g', 0);
        // The rich presence script should be sent as POST data due to potential size.
        $this->richPresence = request()->post('d', '');

        return null;
    }

    protected function process(): array
    {
        $game = Game::find($this->gameId);
        if (!$game) {
            return $this->gameNotFound();
        }

        // Check if user has permission to update this game.
        if (!$this->user->can('updateField', [$game, 'RichPresencePatch'])) {
            return $this->accessDenied();
        }

        if ($game->RichPresencePatch === $this->richPresence) {
            return [
                'Success' => true,
            ];
        }

        $game->RichPresencePatch = $this->richPresence;
        $game->save();

        (new UpsertTriggerVersionAction())->execute(
            $game,
            $this->richPresence,
            versioned: true, // rich presence is always published
            user: $this->user,
        );

        addArticleComment(
            'Server',
            ArticleType::GameModification,
            $game->id,
            "{$this->user->display_name} changed the rich presence script"
        );

        return [
            'Success' => true,
        ];
    }
}
