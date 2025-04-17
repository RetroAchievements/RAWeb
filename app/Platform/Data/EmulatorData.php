<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Emulator;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Emulator')]
class EmulatorData extends Data
{
    /**
     * @param Collection<int, EmulatorDownloadData> $downloads
     * @param Collection<int, PlatformData> $platforms
     * @param Collection<int, SystemData> $systems
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?bool $canDebugTriggers = null,
        public Lazy|string|null $originalName = null,
        public Lazy|bool|null $hasOfficialSupport = null,
        public Lazy|string|null $websiteUrl = null,
        public Lazy|string|null $documentationUrl = null,
        public Lazy|string|null $sourceUrl = null,
        public Lazy|string|null $downloadUrl = null,
        public Lazy|string|null $downloadX64Url = null,
        public Lazy|Collection|null $downloads = null,
        public Lazy|Collection|null $platforms = null,
        public Lazy|Collection|null $systems = null,
    ) {
    }

    public static function fromEmulator(Emulator $emulator): self
    {
        return new self(
            id: $emulator->id,
            name: $emulator->name,
            canDebugTriggers: $emulator->can_debug_triggers,
            websiteUrl: Lazy::create(fn () => $emulator->website_url),
            documentationUrl: Lazy::create(fn () => $emulator->documentation_url),
            sourceUrl: Lazy::create(fn () => $emulator->source_url),
            downloadUrl: Lazy::create(fn () => $emulator->download_url),
            downloadX64Url: Lazy::create(fn () => $emulator->download_x64_url),
            originalName: Lazy::create(fn () => $emulator->original_name),
            hasOfficialSupport: Lazy::create(fn () => $emulator->has_official_support),
            downloads: Lazy::create(fn () => EmulatorDownloadData::collect($emulator->downloads)),
            platforms: Lazy::create(fn () => PlatformData::collect($emulator->platforms)),
            systems: Lazy::create(fn () => SystemData::collect($emulator->systems)),
        );
    }
}
