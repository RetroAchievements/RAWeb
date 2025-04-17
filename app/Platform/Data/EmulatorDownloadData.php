<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('EmulatorDownload')]
class EmulatorDownloadData extends Data
{
    public function __construct(
        public int $id,
        public int $platformId,
        public ?string $label,
        public string $url,
    ) {
    }
}
