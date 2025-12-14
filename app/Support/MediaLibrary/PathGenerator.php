<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary;

use Jenssegers\Optimus\Optimus;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PathGenerator implements \Spatie\MediaLibrary\Support\PathGenerator\PathGenerator
{
    public function __construct(
        private Optimus $optimus,
    ) {
    }

    public function getPath(Media $media): string
    {
        return $this->obfuscatedPath($media);

        /*
         * Keep uploaded originals in a separate space
         */
        // $hashId = $this->optimus->encode($media->id);
        // $prefixFolder = mb_substr((string)$hashId, 0, 3);
        // return 'files/' . $prefixFolder . '/' . $hashId . '/';
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
        $hashId = $this->optimus->encode((int) $media->model_id);
        $prefixFolder = mb_substr((string) $hashId, 0, 3);
        $sha1 = $media->getCustomProperty('sha1');

        $path = $type . '/' . $media->collection_name . '/' . $prefixFolder . '/' . $hashId . '/';

        // Only add the sha1 subdirectory if a sha1 custom property exists.
        if ($sha1) {
            $path .= $sha1 . '/';
        }

        return $path;
    }
}
