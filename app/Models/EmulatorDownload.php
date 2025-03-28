<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmulatorDownload extends BaseModel
{
    protected $fillable = [
        'emulator_id',
        'platform_id',
        'label',
        'url',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Emulator, EmulatorDownload>
     */
    public function emulator(): BelongsTo
    {
        return $this->belongsTo(Emulator::class);
    }

    /**
     * @return BelongsTo<Platform, EmulatorDownload>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    // == scope
}
