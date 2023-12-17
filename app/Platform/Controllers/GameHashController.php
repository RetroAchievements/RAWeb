<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\ArticleType;
use App\Http\Controller;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameHashController extends Controller
{
    protected function resourceName(): string
    {
        return 'game-hash';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    public function show(GameHash $gameHash): void
    {
        dump($gameHash->toArray());
    }

    public function manage(Game $game): View
    {
        $this->authorize('manage', $this->resourceClass());

        return view('platform.manage-hashes-page', [
            'game' => $game,
            'me' => Auth::user(),
        ]);
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
            'patch_url' => 'nullable|url|regex:/github\.com\/RetroAchievements\/RAPatches\/blob\/main\/.*\.zip$/i',
            'source' => 'nullable|url',
        ]);

        $originalAttributes = $gameHash->getOriginal();
        $updatedAttributes = [
            'Name' => $input['name'],
            'Labels' => $input['labels'],
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
        $this->logGameHashUpdate($gameHash, $changedAttributes, Auth::user());

        return response()->json(['message' => __('legacy.success.update')]);
    }

    public function destroy(GameHash $gameHash): JsonResponse
    {
        $gameId = $gameHash->GameID;
        $hash = $gameHash->MD5;
        $user = Auth::user()->User;

        $wasDeleted = $gameHash->delete();

        if (!$wasDeleted) {
            return response()->json(['message' => 'Failed to delete the game hash.'], 500);
        }

        // Log the hash deletion.
        addArticleComment("Server", ArticleType::GameHash, $gameId, "$hash unlinked by $user");

        return response()->json(['message' => __('legacy.success.delete')]);
    }

    private function logGameHashUpdate(GameHash $gameHash, array $changedAttributes, User $user): void
    {
        $commentParts = ["{$gameHash->MD5} updated by {$user->User}."];

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
        addArticleComment("Server", ArticleType::GameHash, $gameHash->GameID, $comment);
    }
}
