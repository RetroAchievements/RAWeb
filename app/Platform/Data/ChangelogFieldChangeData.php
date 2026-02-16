<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ChangelogFieldChange')]
class ChangelogFieldChangeData extends Data
{
    public function __construct(
        public ?string $oldValue = null,
        public ?string $newValue = null,
    ) {
    }
}
