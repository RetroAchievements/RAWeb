<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Concerns\HasStabilityFlags;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class IntegrationRelease extends BaseModel implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;
    use HasStabilityFlags;

    protected $table = 'integration_releases';

    protected $fillable = [
        'minimum',
        'notes',
        'stable',
        'version',
    ];

    protected $with = [
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
        return 'bin/RA_Integration.dll';
    }

    public function getBuildX64PermalinkAttribute(): string
    {
        return 'bin/RA_Integration-x64.dll';
    }

    // == mutators

    // == relations

    // == scopes
}
