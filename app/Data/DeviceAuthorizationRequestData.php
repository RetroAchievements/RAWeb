<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('DeviceAuthorizationRequest')]
class DeviceAuthorizationRequestData extends Data
{
    public function __construct(
        public string $userCode,
        public ?string $state = null,
    ) {
    }

    public static function fromPassportRequest(Request $request): self
    {
        return new self(
            userCode: $request->input('user_code'),
            state: $request->input('state'),
        );
    }
}
