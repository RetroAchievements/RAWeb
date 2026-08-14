<?php

declare(strict_types=1);

namespace App\Api\V2\Data;

use Spatie\LaravelData\Data;

/**
 * The links member of a response that is not a resource document.
 *
 * It holds the URL of the request, which lets a client keep the response and
 * still know where it came from.
 */
class SelfLinkData extends Data
{
    public function __construct(
        public string $self,
    ) {
    }
}
