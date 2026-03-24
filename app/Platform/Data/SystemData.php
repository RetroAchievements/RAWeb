<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\System;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('System')]
class SystemData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public Lazy|bool $active,
        public Lazy|bool $hasAnalogTvOutput,
        public Lazy|string $iconUrl,
        public Lazy|string $manufacturer,
        public Lazy|string $nameFull,
        public Lazy|string $nameShort,
        #[LiteralTypeScriptType('Array<{ width: number; height: number }> | null')]
        public Lazy|array|null $screenshotResolutions,
    ) {
    }

    public static function fromSystem(System $system): self
    {
        return new self(
            id: $system->id,
            name: $system->name,
            active: Lazy::create(fn () => $system->active),
            hasAnalogTvOutput: Lazy::create(fn () => (bool) $system->has_analog_tv_output),
            iconUrl: Lazy::create(fn () => $system->icon_url),
            manufacturer: Lazy::create(fn () => $system->manufacturer),
            nameFull: Lazy::create(fn () => $system->name_full),
            nameShort: Lazy::create(fn () => $system->name_short),
            screenshotResolutions: Lazy::create(fn () => $system->screenshot_resolutions),
        );
    }
}
