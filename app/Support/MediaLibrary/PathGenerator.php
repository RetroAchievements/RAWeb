<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PathGenerator implements \Spatie\MediaLibrary\Support\PathGenerator\PathGenerator
{
    private const LOW_31_BITS_MASK = 0x7FFFFFFF;
    private const TWO_POW_31 = 2_147_483_648.0;

    public function getPath(Media $media): string
    {
        return $this->obfuscatedPath($media);
    }

    public function getPathForConversions(Media $media): string
    {
        /*
         * TODO: check if media has conversions, otherwise use getPath()
         * files' conversion directories are always created
         */

        return $this->obfuscatedPath($media);
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->obfuscatedPath($media);
    }

    private function obfuscatedPath(Media $media): string
    {
        /**
         * Note: singleFile() and onlyKeepLatest() will delete everything if storage for different media is set to same folder
         * add to sub-folder with sha1 as name
         * replace dots that may indicate a sub-resource with a slash to make sub-directories
         */
        $type = str_replace('.', '/', $media->model_type);
        $hashId = $this->obfuscateModelId((int) $media->model_id);
        $prefixFolder = mb_substr((string) $hashId, 0, 3);
        $sha1 = $media->getCustomProperty('sha1');

        $path = $type . '/' . $media->collection_name . '/' . $prefixFolder . '/' . $hashId . '/';

        // Only add the sha1 subdirectory if a sha1 custom property exists.
        if ($sha1) {
            $path .= $sha1 . '/';
        }

        return $path;
    }

    /**
     * We originally used jenssegers/optimus for this, but our config breaks the
     * library in PHP 8.5. We cannot change our config, or it breaks all medialibrary
     * URLs. Therefore, this function is reimplemented from optimus with a tiny PHP 8.5
     * fix.
     *
     * @see https://github.com/jenssegers/optimus
     * @see https://github.com/jenssegers/optimus/issues/59
     */
    private function obfuscateModelId(int $modelId): int
    {
        $prime = (int) config('optimus.prime');
        $xor = (int) config('optimus.random');

        $doesProductFitInInt = $modelId === 0 || abs($prime) <= intdiv(PHP_INT_MAX, abs($modelId));
        if ($doesProductFitInInt) {
            return (($modelId * $prime) & self::LOW_31_BITS_MASK) ^ $xor;
        }

        // When an int product overflows, PHP multiplies the two values as floats instead.
        $product = (float) $modelId * (float) $prime;
        $low31Bits = fmod($product, self::TWO_POW_31);
        if ($low31Bits < 0) {
            $low31Bits += self::TWO_POW_31;
        }

        return ((int) $low31Bits) ^ $xor;
    }
}
