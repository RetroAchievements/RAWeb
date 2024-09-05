<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Illuminate\Support\Facades\File;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameHashLabel')]
class GameHashLabelData extends Data
{
    public function __construct(
        public string $label,
        public ?string $imgSrc
    ) {
    }

    /**
     * @return GameHashLabelData[]
     */
    public static function fromLabelsString(string $labels): array
    {
        $asArray = array_filter(explode(',', $labels));

        return array_map(function (string $label) {
            $imagePath = "/assets/images/labels/" . $label . '.png';
            $publicPath = public_path($imagePath);

            $imgSrc = File::exists($publicPath) ? asset($imagePath) : null;

            return new self(
                label: $label,
                imgSrc: $imgSrc
            );
        }, $asArray);
    }
}
