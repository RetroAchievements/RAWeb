<?php

declare(strict_types=1);

namespace App\Support\Routing;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

/**
 * @property int $id The model's primary key.
 */
trait HasSelfHealingUrls
{
    /**
     * Get the field to use for generating slugs.
     */
    protected function getSlugSourceField(): string
    {
        return 'title';  // Default value
    }

    /**
     * Get the value of the model's route key.
     * This ensures stuff like `route("system.game.index", ["system" => $system])`
     * automatically inserts the slugified URL.
     */
    public function getRouteKey(): string
    {
        return $this->slug;
    }

    public function resolveRouteBinding(mixed $value, $field = null)
    {
        // Skip self-healing for internal-api routes.
        if (str_starts_with(request()->path(), 'internal-api')) {
            return parent::resolveRouteBinding($value, $field);
        }

        // If it's just a number, redirect to the full slug.
        if (is_numeric($value)) {
            $model = static::findOrFail($value);

            // Try to get full request URI. Fall back to the path if it's not available.
            $currentPath = request()->server->get('REQUEST_URI', '/');

            // If we have a nested path, use it.
            if ($currentPath !== '/') {
                $redirectPath = str_replace('/' . $value . '/', '/' . $model->slug . '/', $currentPath);
                throw new HttpResponseException(Redirect::to($redirectPath));
            }

            // Otherwise just redirect to the slug.
            throw new HttpResponseException(Redirect::to($model->slug));
        }

        // Extract the ID from the slug-number format.
        if (preg_match('/.*-(\d+)$/', $value, $matches)) {
            $model = static::findOrFail($matches[1]);

            // If slug doesn't match, redirect to the correct URL.
            if ($value !== $model->slug) {
                $currentPath = request()->server->get('REQUEST_URI', '/');

                if ($currentPath !== '/') {
                    $redirectPath = str_replace('/' . $value . '/', '/' . $model->slug . '/', $currentPath);
                    throw new HttpResponseException(Redirect::to($redirectPath));
                }

                throw new HttpResponseException(Redirect::to($model->slug));
            }

            return $model;
        }

        return static::findOrFail($value);
    }

    public function getSlugAttribute(): string
    {
        // Forward slashes also need to be dasherized.
        // Laravel doesn't handle these automatically because forward slashes
        // obviously have a special meaning in URLs. By the time we reach this
        // point, though, we know better.
        $fieldValue = $this->{$this->getSlugSourceField()};
        $nameWithDashes = str_replace('/', '-', $fieldValue);

        return Str::slug($nameWithDashes) . '-' . $this->id;
    }
}
