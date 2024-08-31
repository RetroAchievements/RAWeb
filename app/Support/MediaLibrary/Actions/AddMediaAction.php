<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary\Actions;

use App\Support\MediaLibrary\RejectedHashes;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class AddMediaAction
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function execute(HasMedia $model, Request|string $source, string $collection): bool
    {
        $file = null;
        $isRemote = false;
        if (is_a($source, Request::class)) {
            if (!$source->file($collection)) {
                return false;
            }
            $requestFile = $source->file($collection);
            if ($requestFile instanceof UploadedFile) {
                $file = $requestFile->getRealPath();
            }
        } else {
            if (mb_strpos($source, 'http') === 0) {
                $isRemote = true;
                $file = $source;
            } else {
                $file = realpath($source);
            }
        }

        if (!$file) {
            return false;
        }

        /*
         * check if model is morph-mapped; only allow morph mapped models
         * otherwise the fully qualified class name will end up in the file path
         */
        if (!resource_type($model::class)) {
            return false;
        }

        if (!($hash = $this->checkHash($file, $collection))) {
            return false;
        }

        if ($model->getMedia($collection)->where('custom_properties.sha1', '=', $hash)->count()) {
            /*
             * TODO: original was already uploaded - reorder to top
             * Successful response as the file does exist
             */
            return true;
        }

        /*
         * check if model implements InteractsWithMedia trait
         */
        if (!method_exists($model, 'addMediaFromRequest')) {
            return false;
        }
        if (!method_exists($model, 'addMediaFromUrl')) {
            return false;
        }
        if (is_a($source, Request::class)) {
            $fileAdder = $model->addMediaFromRequest($collection);
        } elseif ($isRemote) {
            $fileAdder = $model->addMediaFromUrl($file);
        } else {
            /**
             * do not delete local files, this would delete production files while syncing
             */
            $fileAdder = $model->addMedia($file)->preservingOriginal();
        }

        try {
            $fileAdder->withCustomProperties(['sha1' => $hash])->toMediaCollection($collection);
        } catch (Exception) {
            /*
             * most likely a zero byte file
             */
            return false;
        }

        return true;
    }

    private function checkHash(string $file, string $collection): bool|string|null
    {
        try {
            $hash = sha1_file($file);
        } catch (Exception) {
            return null;
        }

        switch ($collection) {
            case 'avatar':
                if (in_array($hash, RejectedHashes::AVATAR_HASHES)) {
                    return false;
                }
                break;
            case 'image':
                if (in_array($hash, RejectedHashes::IMAGE_HASHES_NEWS)) {
                    return false;
                }
                break;
            case 'icon':
                if (in_array($hash, RejectedHashes::IMAGE_HASHES_GAMES)) {
                    return false;
                }
                break;
        }

        return $hash;
    }
}
