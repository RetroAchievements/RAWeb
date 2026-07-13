<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\OAuthGrant;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ConnectedOAuthApplication')]
class ConnectedOAuthApplicationData extends Data
{
    /** @param string[] $scopes */
    public function __construct(
        public string $clientId,
        public string $name,
        public array $scopes,
        public Carbon $connectedAt,
    ) {
    }

    public static function fromGrant(OAuthGrant $grant): self
    {
        return new self(
            clientId: $grant->client_id,
            name: $grant->client->name,
            scopes: $grant->scopes,
            connectedAt: $grant->first_granted_at,
        );
    }
}
