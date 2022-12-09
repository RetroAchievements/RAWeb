<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use Database\Factories\Legacy\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class Game extends BaseModel
{
    use HasFactory;

    protected $table = 'GameData';

    protected static function newFactory(): GameFactory
    {
        return GameFactory::new();
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class, 'GameID');
    }

    public function console(): BelongsTo
    {
        return $this->belongsTo(System::class, 'ConsoleID');
    }
}
