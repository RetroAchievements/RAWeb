<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\Carbon;
use Laravel\Passport\Client;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('OAuthClient')]
class OAuthClientData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var string[] */
        public array $redirectUris,
        /** @var string[] */
        public array $grantTypes,
        public bool $revoked,
        public Carbon $createdAt,
        public Carbon $updatedAt,
    ) {
    }

    public static function fromClient(Client $client): self
    {
        return new self(
            id: (string) $client->id,
            name: $client->name,
            redirectUris: $client->redirect_uris,
            grantTypes: $client->grant_types,
            revoked: $client->revoked,
            createdAt: Carbon::parse($client->created_at),
            updatedAt: Carbon::parse($client->updated_at),
        );
    }
}
