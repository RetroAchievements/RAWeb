<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use App\Data\UserPermissionsData;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('CommentPageProps<TItems = App.Community.Data.Comment>')]
class CommentPagePropsData extends Data
{
    /**
     * These computed properties are phantom fields for TypeScript generation only.
     * They are never serialized - toArray() outputs the entity under a dynamic key
     * (eg: 'game', 'achievement') based on $entityKey. TypeScript sees all four
     * as optional properties, but only one will exist at runtime.
     */
    #[Computed]
    #[LiteralTypeScriptType('App.Platform.Data.Achievement | undefined')]
    public ?string $achievement = null;

    #[Computed]
    #[LiteralTypeScriptType('App.Platform.Data.Game | undefined')]
    public ?string $game = null;

    #[Computed]
    #[LiteralTypeScriptType('App.Platform.Data.Leaderboard | undefined')]
    public ?string $leaderboard = null;

    #[Computed]
    #[LiteralTypeScriptType('App.Data.User | undefined')]
    public ?string $targetUser = null;

    public function __construct(
        public UserPermissionsData $can,
        public bool $canComment,
        public bool $isSubscribed,
        public PaginatedData $paginatedComments,
        private Data $entity,
        private string $entityKey,
    ) {
    }

    public function toArray(): array
    {
        return [
            $this->entityKey => $this->entity->toArray(), // outputs the computed entity
            'can' => $this->can->toArray(),
            'canComment' => $this->canComment,
            'isSubscribed' => $this->isSubscribed,
            'paginatedComments' => $this->paginatedComments->toArray(),
        ];
    }
}
