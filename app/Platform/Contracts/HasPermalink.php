<?php

declare(strict_types=1);

namespace App\Platform\Contracts;

interface HasPermalink
{
    public function getPermalinkAttribute(): string;
}
