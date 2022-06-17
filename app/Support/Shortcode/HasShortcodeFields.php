<?php

declare(strict_types=1);

namespace App\Support\Shortcode;

use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * Makes sure rich contents in models are stored correctly
 */
trait HasShortcodeFields
{
    public static function bootHasShortcodeFields(): void
    {
        static::saving(function ($model) {
            /**
             * parse contents for any shortcodes that have to be adjusted
             * like user which should reference the id, not only the username
             */
            $shortcodeFields = $model->getShortcodeFields();
            if (empty($shortcodeFields)) {
                throw new Exception('Model has HasShortcode trait but no $shortCodeFields set.');
            }

            foreach ($shortcodeFields as $shortcodeField) {
                /** @var Model $model */
                /** @var ?string $value */
                $value = $model->getAttribute($shortcodeField);

                if ($value !== null) {
                    $model->setAttribute($shortcodeField, normalize_user_shortcodes($value));
                }
            }
        });
    }

    protected function getShortcodeFields(): array
    {
        return $this->shortcodeFields ?? [];
    }
}
