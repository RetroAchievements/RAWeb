<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AuthorizeDevicePageProps')]
class AuthorizeDevicePagePropsData extends Data
{
    public function __construct(
        public OAuthClientData $client,
        /** @var string[] */
        public array $scopes,
        public DeviceAuthorizationRequestData $request,
        public string $authToken,
    ) {
    }
}
