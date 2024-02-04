<?php

declare(strict_types=1);

namespace App\Concerns;

use Spatie\Image\Manipulations;

trait HasAvatar
{
    public static function bootHasAvatar(): void
    {
    }

    // == media

    public function registerAvatarMediaCollection(): void
    {
        $this->addMediaCollection('avatar')
            ->useFallbackUrl(asset('assets/images/user/avatar.webp'))
            // ->useFallbackPath(public_path('/assets/images/user/avatar.webp'))
            ->singleFile()
            // ->onlyKeepLatest(3)
            // ->acceptsFile(function (File $file) {
            //     return $file->mimeType === 'image/jpeg';
            // })
            ->registerMediaConversions(function () {
                /**
                 * @see https://docs.spatie.be/image/v1/image-manipulations/resizing-images/
                 * Note: FIT_CONTAIN will upsize/downsize but not fill to full size, using FIT_FILL to do that
                 * FIT_FILL and FIT_MAX will not upsize
                 * Call ->apply() to execute in sequence
                 * @see /config/media.php
                 */
                $iconSizes = [
                    /*
                     * used on profile pages
                     */
                    '2xl',

                    /*
                     * used everywhere else
                     */
                    'md',
                ];

                foreach ($iconSizes as $iconSize) {
                    $width = config('media.icon.' . $iconSize . '.width');
                    $height = config('media.icon.' . $iconSize . '.height');
                    $this->addMediaConversion($iconSize)
                        ->nonQueued()
                        ->format('png')
                        ->fit(Manipulations::FIT_CONTAIN, $width, $height)->apply()
                        ->fit(Manipulations::FIT_FILL, $width, $height)
                        ->optimize();
                }
            });
    }

    // == accessors

    /**
     * not using permalink urls for avatar to be safe for username change
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->getAvatar2xlUrlAttribute();
    }

    public function getAvatarMdUrlAttribute(): string
    {
        return $this->hasMedia('avatar')
            ? $this->getFirstMediaUrl('avatar', 'md')
            : $this->getFallbackMediaUrl('avatar');
    }

    public function getAvatar2xlUrlAttribute(): string
    {
        return $this->hasMedia('avatar')
            ? $this->getFirstMediaUrl('avatar', '2xl')
            : $this->getFallbackMediaUrl('avatar');
    }

    /*
     * Note: this is not used! Do not expose it. Only emulators access avatars this way
     * and are only symlinked on the media host - requests for those to the main domain are redirected on the
     * web server level.
     */
    // public function getAvatarPermalinkAttribute(): string
    // {
    //     return 'UserPic/' . $this->username . '.png';
    // }
}
