<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Concerns\HasStabilityFlags;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EmulatorRelease extends BaseModel implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;
    use HasStabilityFlags;

    protected $table = 'emulator_releases';

    protected $fillable = [
        'emulator_id',
        'minimum',
        'notes',
        'stable',
        'version',
    ];

    protected $with = [
        'emulator',
        'media',
    ];

    // == media

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('build_x86')
            // ->useFallbackUrl('/assets/images/user/avatar.webp')
            // ->useFallbackPath(public_path('/assets/images/user/avatar.webp'))
            ->singleFile()
            // ->onlyKeepLatest(3)
            // ->acceptsFile(function (File $file) {
            //     return $file->mimeType === 'image/jpeg';
            // })
        ;
        $this->addMediaCollection('build_x64')
            ->singleFile();
    }

    // == accessors

    public function getBuildX86PermalinkAttribute(): string
    {
        return 'bin/' . $this->getAttribute('handle') . '.zip';
    }

    public function getBuildX64PermalinkAttribute(): string
    {
        return 'bin/' . $this->getAttribute('handle') . '-x64.zip';
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Emulator, EmulatorRelease>
     */
    public function emulator(): BelongsTo
    {
        return $this->belongsTo(Emulator::class);
    }

    // == scopes
}
