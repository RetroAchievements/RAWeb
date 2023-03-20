<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use Database\Factories\Legacy\SystemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class System extends BaseModel
{
    use HasFactory;

    protected $table = 'Console';

    protected static function newFactory(): SystemFactory
    {
        return SystemFactory::new();
    }

    /**
     * @return HasMany<Game>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'ConsoleID');
    }
}
