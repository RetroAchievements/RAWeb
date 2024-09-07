<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\ArticleType;
use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\User;
use App\Platform\Data\GameData;
use App\Platform\Data\GameHashData;
use App\Platform\Data\GameHashesPagePropsData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameHashController extends Controller
{
    protected function resourceName(): string
    {
        return 'game-hash';
    }

    public function index(Request $request, Game $game): InertiaResponse
    {
        $this->authorize('viewAny', $this->resourceClass());

        $gameData = GameData::fromGame($game)->include('badgeUrl', 'forumTopicId', 'system');
        $hashes = GameHashData::fromCollection($game->hashes);
        $can = UserPermissionsData::fromUser($request->user())->include('manageGameHashes');

        $props = new GameHashesPagePropsData($gameData, $hashes, $can);

        return Inertia::render('game/[game]/hashes', $props);
    }

    public function show(GameHash $gameHash): void
    {
        dump($gameHash->toArray());
    }

    public function edit(GameHash $gameHash): void
    {
    }

    public function update(Request $request, GameHash $gameHash): JsonResponse
    {
        $this->authorize('update', $this->resourceClass());

        $input = $request->validate([
            'name' => 'required|string',
            'labels' => 'required|string',
            'patch_url' => [
                'nullable',
                'url',
                'regex:/^https:\/\/github\.com\/RetroAchievements\/RAPatches\/raw\/main\/.*\.(zip|7z)$/i',
            ],
            'source' => 'nullable|url',
        ]);

        $originalAttributes = $gameHash->getOriginal();
        $updatedAttributes = [
            'name' => $input['name'],
            'labels' => $input['labels'],
            'patch_url' => $input['patch_url'] ?? null,
            'source' => $input['source'] ?? null,
        ];

        $changedAttributes = array_filter($updatedAttributes, function ($value, $key) use ($originalAttributes) {
            return $originalAttributes[$key] != $value;
        }, ARRAY_FILTER_USE_BOTH);
        $isChanging = !empty($changedAttributes);

        if (!$isChanging) {
            return response()->json(['message' => 'No changes were made.'], 200);
        }

        $gameHash->update($updatedAttributes);

        /** @var User $user */
        $user = Auth::user();
        $this->logGameHashUpdate($gameHash, $changedAttributes, $user);

        return response()->json(['message' => __('legacy.success.update')]);
    }

    public function destroy(GameHash $gameHash): JsonResponse
    {
        $gameId = $gameHash->game_id;
        $hash = $gameHash->md5;
        $user = Auth::user()->User;

        $wasDeleted = $gameHash->forceDelete();

        if (!$wasDeleted) {
            return response()->json(['message' => 'Failed to delete the game hash.'], 500);
        }

        // Log the hash deletion.
        addArticleComment("Server", ArticleType::GameHash, $gameId, "$hash unlinked by $user");

        return response()->json(['message' => __('legacy.success.delete')]);
    }

    private function logGameHashUpdate(GameHash $gameHash, array $changedAttributes, User $user): void
    {
        $commentParts = ["{$gameHash->md5} updated by {$user->User}."];

        foreach ($changedAttributes as $attribute => $newValue) {
            $newValueDisplay = $newValue ?? 'None';

            switch ($attribute) {
                case 'Name':
                    $commentParts[] = "File Name: \"{$newValueDisplay}\".";
                    break;
                case 'Labels':
                    $commentParts[] = "Label: \"{$newValueDisplay}\".";
                    break;
                case 'patch_url':
                    $commentParts[] = $newValue ? "RAPatches URL updated to: {$newValue}." : "RAPatches URL removed.";
                    break;
                case 'source':
                    $commentParts[] = $newValue ? "Resource Page URL updated to: {$newValue}." : "Resource Page URL removed.";
                    break;
            }
        }

        $comment = implode(' ', $commentParts);
        addArticleComment("Server", ArticleType::GameHash, $gameHash->game_id, $comment);
    }
}
