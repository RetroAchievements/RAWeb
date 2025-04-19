<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Achievement;
use App\Models\ForumTopic;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UserPermissions')]
class UserPermissionsData extends Data
{
    public function __construct(
        public Lazy|bool $authorizeForumTopicComments,
        public Lazy|bool $createGameForumTopic,
        public Lazy|bool $createTriggerTicket,
        public Lazy|bool $createUsernameChangeRequest,
        public Lazy|bool $deleteForumTopic,
        public Lazy|bool $develop,
        public Lazy|bool $manageEmulators,
        public Lazy|bool $manageForumTopicComments,
        public Lazy|bool $manageForumTopics,
        public Lazy|bool $manageEvents,
        public Lazy|bool $manageGameHashes,
        public Lazy|bool $manageGameSets,
        public Lazy|bool $manipulateApiKeys,
        public Lazy|bool $updateAvatar,
        public Lazy|bool $updateForumTopic,
        public Lazy|bool $updateMotto,
    ) {
    }

    public static function fromUser(
        ?User $user,
        // TODO `?Model $triggerable`
        Achievement|Leaderboard|null $triggerable = null,
        ?Game $game = null,
        ?ForumTopic $forumTopic = null,
    ): self {
        return new self(
            authorizeForumTopicComments: Lazy::create(fn () => $user
                ? $user->can('authorize', \App\Models\ForumTopicComment::class)
                : false
            ),
            createGameForumTopic: Lazy::create(fn () => $user && $game
                ? $user->can('createForumTopic', $game)
                : false
            ),
            createTriggerTicket: Lazy::create(fn () => $user && $triggerable
                ? $user->can('createFor', [\App\Models\TriggerTicket::class, $triggerable])
                : $user?->can('create', \App\Models\TriggerTicket::class) ?? false
            ),
            createUsernameChangeRequest: Lazy::create(fn () => $user ? $user->can('create', \App\Models\UserUsername::class) : false),
            deleteForumTopic: Lazy::create(fn () => $user && $forumTopic ? $user->can('delete', $forumTopic) : false),
            develop: Lazy::create(fn () => $user ? $user->can('develop') : false),
            manageEmulators: Lazy::create(fn () => $user ? $user->can('manage', \App\Models\Emulator::class) : false),
            manageForumTopicComments: Lazy::create(fn () => $user ? $user->can('manage', \App\Models\ForumTopicComment::class) : false),
            manageForumTopics: Lazy::create(fn () => $user ? $user->can('manage', ForumTopic::class) : false),
            manageEvents: Lazy::create(fn () => $user ? $user->can('manage', \App\Models\Event::class) : false),
            manageGameHashes: Lazy::create(fn () => $user ? $user->can('manage', \App\Models\GameHash::class) : false),
            manageGameSets: Lazy::create(fn () => $user ? $user->can('manage', \App\Models\GameSet::class) : false),
            manipulateApiKeys: Lazy::create(fn () => $user ? $user->can('manipulateApiKeys', $user) : false),
            updateAvatar: Lazy::create(fn () => $user ? $user->can('updateAvatar', $user) : false),
            updateForumTopic: Lazy::create(fn () => $user && $forumTopic ? $user->can('update', $forumTopic) : false),
            updateMotto: Lazy::create(fn () => $user ? $user->can('updateMotto', $user) : false),
        );
    }
}
