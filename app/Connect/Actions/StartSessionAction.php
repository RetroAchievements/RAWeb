<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\CanBeDelegated;
use App\Enums\ClientSupportLevel;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\User;
use App\Platform\Events\PlayerSessionHeartbeat;
use App\Platform\Services\UserAgentService;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StartSessionAction extends BaseAuthenticatedApiAction
{
    use CanBeDelegated;

    protected ?Game $game;
    protected ?string $gameHashMd5;
    protected ?bool $hardcore;

    public function execute(User $user, Game $game, ?string $gameHashMd5 = null, ?bool $hardcore = null): array
    {
        $this->user = $user;
        $this->game = $game;
        $this->gameHashMd5 = $gameHashMd5;
        $this->hardcore = $hardcore;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['g'])) {
            return $this->missingParameters();
        }

        $gameId = request()->integer('g', 0);
        if (VirtualGameIdService::isVirtualGameId($gameId)) {
            // don't create sessions for incompatible hashes
            return $this->emptyResponse();
        }

        $this->game = Game::find($gameId);
        if (!$this->game) {
            return $this->gameNotFound();
        }

        $result = $this->applyDelegationForGame($request, $this->game);
        if ($result !== null) {
            return $result;
        }

        $this->gameHashMd5 = request()->input('m');

        $hardcore = request()->integer('h');
        if ($hardcore !== null) {
            $this->hardcore = ($hardcore === 1);
        }

        return null;
    }

    protected function process(): array
    {
        $gameHash = null;
        if ($this->gameHashMd5) {
            $gameHash = GameHash::whereMd5($this->gameHashMd5)->first();
        }

        // if multiset is enabled, redirect the heartbeat to the root game.
        if (config('feature.enable_multiset')) {
            $this->game = (new ResolveRootGameFromGameAndGameHashAction())->execute($gameHash, $this->game, $this->user);
        }

        PlayerSessionHeartbeat::dispatch($this->user, $this->game, null, $gameHash);

        $response = $this->emptyResponse();

        // split the unlocks into hardcore and non-hardcore
        $userUnlocks = getUserAchievementUnlocksForGame($this->user, $this->game->id);
        $userUnlocks = reactivateUserEventAchievements($this->user, $userUnlocks);
        foreach ($userUnlocks as $achId => $unlock) {
            if (array_key_exists('DateEarnedHardcore', $unlock)) {
                $response['HardcoreUnlocks'][] = [
                    'ID' => $achId,
                    'When' => strtotime($unlock['DateEarnedHardcore']),
                ];
            } else {
                $response['Unlocks'][] = [
                    'ID' => $achId,
                    'When' => strtotime($unlock['DateEarned']),
                ];
            }
        }

        // if the user is using an unknown or outdated client, mark the warning
        // achievement as earned in softcore so it only pops in hardcore.
        $userAgentService = new UserAgentService();
        $clientSupportLevel = $userAgentService->getSupportLevel(request()->header('User-Agent'));
        if ($clientSupportLevel !== ClientSupportLevel::Full) {
            // don't allow outdated client popup to appear in softcore mode
            $response['Unlocks'][] = [
                'ID' => Achievement::CLIENT_WARNING_ID,
                'When' => Carbon::now()->unix(),
            ];
        }

        return $response;
    }

    private function emptyResponse(): array
    {
        return [
            'Success' => true,
            'ServerNow' => Carbon::now()->timestamp,
            'HardcoreUnlocks' => [],
            'Unlocks' => [],
        ];
    }
}
