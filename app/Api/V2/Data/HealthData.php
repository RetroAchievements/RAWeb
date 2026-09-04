<?php

declare(strict_types=1);

namespace App\Api\V2\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * The result of a liveness check.
 *
 * The check says only that the API responds. It says nothing about the state of
 * any resource.
 */
class HealthData extends Data
{
    public function __construct(
        public string $status,
        public CarbonImmutable $timestamp,
    ) {
    }
}
