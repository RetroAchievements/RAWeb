<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\System;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('System')]
class SystemData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public Lazy|string $nameFull,
        public Lazy|string $nameShort,
        public Lazy|string $iconUrl,
    ) {
    }

    public static function fromSystem(System $system): self
    {
        return new self(
            id: $system->id,
            name: $system->name,
            nameFull: Lazy::create(fn () => $system->name_full),
            nameShort: Lazy::create(fn () => $system->name_short),
            iconUrl: Lazy::create(fn () => $system->icon_url),
        );
    }
}
