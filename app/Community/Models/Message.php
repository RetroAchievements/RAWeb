<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends BaseModel
{
    use SoftDeletes;

    protected $casts = [
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'title',
            'body',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // return $this->isPublished();
        return true;
    }

    // == accessors

    // == mutators

    // == relations

    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Site\Models\User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(\App\Site\Models\User::class, 'recipient_id');
    }

    // == scopes

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
