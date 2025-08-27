<?php

declare(strict_types=1);

namespace App\Community\Data;

use Spatie\LaravelData\Data;

class ProcessedDiscordMessageData extends Data
{
    public function __construct(
        public int $color,
        public bool $isForum,
        public string $messageBody,
        public string $threadTitle,
        public string $webhookUrl,
    ) {
    }
}
