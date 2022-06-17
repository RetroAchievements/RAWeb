<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary\Actions;

use Spatie\MediaLibrary\HasMedia;

class DeleteLatestMediaAction
{
    public function execute(HasMedia $hasMedia, string $collection): void
    {
        /*
         * remove local entry
         */
        if ($hasMedia->hasMedia($collection)) {
            $hasMedia->getMedia($collection)->last()->delete();
        }
    }
}
