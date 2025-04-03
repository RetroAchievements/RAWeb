<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Emulator;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Emulator')]
class EmulatorData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?bool $supportsToolkit = null,
    ) {
    }

    public static function fromEmulator(Emulator $emulator): self
    {
        return new self(
            id: $emulator->id,
            name: $emulator->name,
            supportsToolkit: $emulator->supports_toolkit,
        );
    }
}
