<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('OAuthRequest')]
class OAuthRequestData extends Data
{
    public function __construct(
        public string $clientId,
        public string $redirectUri,
        public string $responseType,
        public ?string $scope = null,
        public ?string $state = null,
    ) {
    }

    public static function fromPassportRequest(Request $request): self
    {
        return new self(
            clientId: $request->input('client_id'),
            redirectUri: $request->input('redirect_uri'),
            responseType: $request->input('response_type'),
            scope: $request->input('scope'),
            state: $request->input('state'),
        );
    }
}
