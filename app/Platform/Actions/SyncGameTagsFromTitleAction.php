<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;

// TODO remove legacy tags from game titles, "~Hack~ Game Title" -> "Game Title"

class SyncGameTagsFromTitleAction
{
    private array $validTags = [
        'Demo',
        'Hack',
        'Homebrew',
        'Prototype',
        'Test Kit',
        'Unlicensed',
        'Z',
    ];

    /**
     * Sync tags based on changes in the game title.
     * When $originalTitle is null, it's likely a game being created.
     * When $newTitle is null, sync based on the current title only (useful for initial syncs).
     */
    public function execute(Game $game, ?string $originalTitle = null, ?string $newTitle = null): void
    {
        // For new models or the initial taggables sync, just attach tags from the current title.
        if ($originalTitle === null || $newTitle === null) {
            $currentTitle = $originalTitle ?? $newTitle ?? $game->title;
            $currentTags = $this->extractLegacyTags($currentTitle);

            foreach ($currentTags as $legacyTagName) {
                if (!$game->tags()->whereType('game')->where('name->en', $legacyTagName)->exists()) {
                    $game->attachTag($legacyTagName, 'game');
                }
            }

            return;
        }

        $legacyTagsInOriginal = $this->extractLegacyTags($originalTitle);
        $legacyTagsInNew = $this->extractLegacyTags($newTitle);

        // When someone is changing the title of a game, we need to handle
        // the possibility that tags are either being attached or detached.
        $tagsToRemove = array_diff($legacyTagsInOriginal, $legacyTagsInNew);
        foreach ($tagsToRemove as $legacyTagName) {
            $game->detachTag($legacyTagName, 'game');
        }

        $tagsToAdd = array_diff($legacyTagsInNew, $legacyTagsInOriginal);
        foreach ($tagsToAdd as $legacyTagName) {
            // If the dev uses some weird tag or makes a typo in a tag name, skip it.
            if (!in_array($legacyTagName, $this->validTags)) {
                continue;
            }

            $game->attachTag($legacyTagName, 'game');
        }
    }

    private function extractLegacyTags(string $title): array
    {
        $tags = [];
        $legacyTagPattern = '/~([^~]+)~/';

        if (preg_match_all($legacyTagPattern, $title, $matches)) {
            $tags = $matches[1];
        }

        return $tags;
    }
}
