<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ParsedUserAgent')]
class ParsedUserAgentData extends Data
{
    public function __construct(
        public string $client,
        public string $clientVersion,
        public ?string $os = null,
        public ?string $integrationVersion = null,
        public ?array $extra = null,
        public ?string $clientVariation = null,
    ) {
    }
}
