<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class SimilarGamesCapExceededException extends RuntimeException
{
    public function __construct(
        public readonly int $offendingGameId,
        public readonly int $cap,
    ) {
        parent::__construct("Game #{$offendingGameId} would exceed the {$cap}-similar-game cap.");
    }
}
