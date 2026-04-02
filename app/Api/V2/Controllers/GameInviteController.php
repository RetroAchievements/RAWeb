<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Community\Actions\CancelGameInviteAction;
use App\Community\Actions\CreateGameInviteAction;
use App\Community\Actions\RespondToGameInviteAction;
use App\Models\Game;
use App\Models\GameInvite;
use App\Models\User;
use App\Platform\Enums\GameInviteStatus;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class GameInviteController extends JsonApiController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;

    /**
     * Override store to use the CreateGameInviteAction.
     */
    public function store(Route $route, StoreContract $store)
    {
        /** @var ResourceRequest $request */
        $request = ResourceRequest::forResource($route->resourceType());

        $query = ResourceQuery::queryOne($route->resourceType());

        $validated = $request->validated();

        /** @var User $sender */
        $sender = $request->user();

        // Resolve game from flattened relationship data
        $gameId = $validated['game']['id'] ?? null;
        /** @var Game $game */
        $game = Game::findOrFail($gameId);

        // Resolve recipient from flattened relationship data
        $recipientId = $validated['recipient']['id'] ?? null;
        $recipient = User::where('ulid', $recipientId)
            ->orWhere('display_name', $recipientId)
            ->orWhere('username', $recipientId)
            ->firstOrFail();

        // Create the invite using existing action
        $invite = (new CreateGameInviteAction())->execute(
            $sender,
            $recipient,
            $game,
            $validated['message'] ?? null
        );

        return DataResponse::make($invite)
            ->withQueryParameters($query)
            ->didCreate();
    }

    /**
     * Override update to handle status transitions.
     */
    public function update(Route $route, StoreContract $store)
    {
        /** @var ResourceRequest $request */
        $request = ResourceRequest::forResource($route->resourceType());

        $query = ResourceQuery::queryOne($route->resourceType());

        /** @var GameInvite $invite */
        $invite = $route->model();
        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        // Handle status transitions
        if (isset($validated['status'])) {
            $newStatus = GameInviteStatus::from($validated['status']);

            if ($newStatus === GameInviteStatus::Accepted || $newStatus === GameInviteStatus::Declined) {
                $invite = (new RespondToGameInviteAction())->execute($invite, $user, $newStatus);
            } elseif ($newStatus === GameInviteStatus::Canceled) {
                $invite = (new CancelGameInviteAction())->execute($invite, $user);
            } else {
                throw new \InvalidArgumentException('Invalid status transition.');
            }
        }

        return DataResponse::make($invite)->withQueryParameters($query);
    }
}
