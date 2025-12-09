<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('DeviceCodeRequest')]
class DeviceCodeRequestData extends Data
{
    public function __construct(
        public ?string $clientId = null,
    ) {
    }

    public static function fromPassportRequest(Request $request): self
    {
        return new self(
            clientId: $request->input('client_id'),
        );
    }
}
