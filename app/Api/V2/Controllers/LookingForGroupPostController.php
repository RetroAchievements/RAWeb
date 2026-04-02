<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Community\Actions\CreateLookingForGroupPostAction;
use App\Models\Game;
use App\Models\LookingForGroupPost;
use App\Models\User;
use App\Community\Enums\LookingForGroupStatus;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class LookingForGroupPostController extends JsonApiController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;

    /**
     * Override store to use the CreateLookingForGroupPostAction.
     */
    public function store(Route $route, StoreContract $store)
    {
        /** @var ResourceRequest $request */
        $request = ResourceRequest::forResource($route->resourceType());

        $query = ResourceQuery::queryOne($route->resourceType());

        $validated = $request->validated();

        /** @var User $creator */
        $creator = $request->user();

        // Resolve game from relationship data
        $gameId = $validated['game']['id'];
        /** @var Game $game */
        $game = Game::findOrFail($gameId);

        // Create the post using existing action
        $post = (new CreateLookingForGroupPostAction())->execute(
            $creator,
            $game,
            $validated['title'],
            $validated['note'] ?? null,
            $validated['maxPlayers'] ?? null,
            isset($validated['scheduledFor']) ? new \DateTime($validated['scheduledFor']) : null,
            null // Use default expiration
        );

        return DataResponse::make($post)
            ->withQueryParameters($query)
            ->didCreate();
    }

    /**
     * Override update to handle status changes.
     */
    public function update(Route $route, StoreContract $store)
    {
        /** @var ResourceRequest $request */
        $request = ResourceRequest::forResource($route->resourceType());

        $query = ResourceQuery::queryOne($route->resourceType());

        /** @var LookingForGroupPost $post */
        $post = $route->model();
        $validated = $request->validated();

        // Handle status changes - only if status is actually being changed
        if (isset($validated['status'])) {
            $statusValue = is_string($validated['status']) ? $validated['status'] : $validated['status']->value;

            // Only process if the status is actually changing
            if ($statusValue !== $post->status->value) {
                $newStatus = LookingForGroupStatus::from($statusValue);

                // Only allow status changes by creator
                if ($post->creator_user_id !== $request->user()->id) {
                    throw new \InvalidArgumentException('Only the creator can change the post status.');
                }

                if (!$post->status->canTransitionTo($newStatus)) {
                    throw new \InvalidArgumentException('Invalid status transition.');
                }

                $post->update(['status' => $newStatus]);
            }
        }

        // Update other attributes
        if (isset($validated['title'])) {
            $post->update(['title' => $validated['title']]);
        }

        if (isset($validated['note'])) {
            $post->update(['note' => $validated['note']]);
        }

        if (isset($validated['maxPlayers'])) {
            // Validate max players doesn't go below accepted count
            $newMax = $validated['maxPlayers'];
            $acceptedCount = $post->getAcceptedPlayersCount();

            if ($newMax < $acceptedCount) {
                throw new \InvalidArgumentException("Max players cannot be less than the number of accepted players ({$acceptedCount}).");
            }

            $post->update(['max_players' => $newMax]);
        }

        if (isset($validated['scheduledFor'])) {
            $post->update(['scheduled_for' => new \DateTime($validated['scheduledFor'])]);
        }

        return DataResponse::make($post->refresh())->withQueryParameters($query);
    }
}
