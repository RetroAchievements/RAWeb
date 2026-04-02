<?php

declare(strict_types=1);

namespace App\Api\V2\Messages;

use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Schema;

class MessageSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Message::class;

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'messages';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('body', 'body'),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('author')->type('users')->readOnly(),
            BelongsTo::make('sentBy')->type('users')->readOnly(),
            BelongsTo::make('messageThread')->type('message-threads'),
        ];
    }

    /**
     * Build an index query for this resource.
     * Messages should only be accessed via their thread relationship.
     *
     * @param Builder<Message> $query
     * @return Builder<Message>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $query->whereRaw('1 = 0'); // Prevent direct listing of all messages
    }

    /**
     * Build a show query for this resource.
     * Only allow access if the user is a participant in the thread.
     *
     * @param Builder<Message> $query
     * @return Builder<Message>
     */
    public function showQuery(?object $model, Builder $query): Builder
    {
        /** @var User $user */
        $user = request()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('messageThread', function ($q) use ($user) {
            $q->whereHas('participants', function ($pq) use ($user) {
                $pq->where('user_id', $user->id)
                  ->whereNull('deleted_at');
            });
        });
    }
}
