<?php

declare(strict_types=1);

namespace App\Support\HashId;

use Jenssegers\Optimus\Optimus;

trait HasHashId
{
    protected function getHashIdAttribute(): int
    {
        return app(Optimus::class)->encode($this->id);
    }
}
