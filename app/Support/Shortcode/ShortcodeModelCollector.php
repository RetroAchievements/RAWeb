<?php

declare(strict_types=1);

namespace App\Support\Shortcode;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Collects all shortcode model tags to be eager loaded
 * otherwise each reference to a model results in a separate query + relations
 */
class ShortcodeModelCollector
{
    private static Collection $staged;
    private static Collection $models;

    public static function collect(null|string|array|Collection $content): void
    {
        self::$staged ??= collect();
        self::$models ??= collect();

        $contents = $content instanceof Collection ? $content : collect($content);

        /*
         * extract
         */
        foreach ($contents as $content) {
            parseShortcodes($content, true);
        }

        foreach (self::$staged as $modelClass => $modelIds) {
            $models = (new $modelClass())->whereIn('id', $modelIds)->get()->keyBy('id');
            self::$models[$modelClass] ??= collect();
            foreach ($models as $modelId => $model) {
                self::$models[$modelClass]->put($modelId, $model);
            }
        }

        self::$staged = collect();
    }

    public static function add(string $modelClass, int $modelId): void
    {
        self::$staged->put($modelClass, (self::$staged->get($modelClass) ?? collect())->merge([$modelId]));
    }

    public static function get(string $modelClass, int $modelId): ?Model
    {
        if (self::$models->isEmpty() || !self::$models->get($modelClass)) {
            throw new Exception('ModelCollector did not collect any ' . $modelClass . ' yet. Run it on all rich contents for eager loading.');
        }

        return self::$models->get($modelClass)->get($modelId);
    }
}
