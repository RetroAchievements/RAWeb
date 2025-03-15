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
        return 'title'; // Default value.
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

    /**
     * Handles redirection to the correct URL based on the model and current path.
     *
     * @param string $incorrectValue the incorrect value from the URL
     * @param self $model the model instance with the correct slug
     */
    protected function redirectToCorrectUrl(string $incorrectValue, $model): never
    {
        $currentPath = request()->server->get('REQUEST_URI', '/');

        // Handle nested paths (ie: "/system/1/games").
        if (str_contains($currentPath, '/' . $incorrectValue . '/')) {
            // Preserve the path structure but replace the incorrect value with the slug
            $redirectPath = str_replace('/' . $incorrectValue . '/', '/' . $model->slug . '/', $currentPath);
            throw new HttpResponseException(Redirect::to($redirectPath));
        }

        // Handle direct paths ending with the incorrect value (ie: "/system/1").
        if (str_ends_with($currentPath, '/' . $incorrectValue)) {
            $redirectPath = substr($currentPath, 0, -strlen($incorrectValue)) . $model->slug;
            throw new HttpResponseException(Redirect::to($redirectPath));
        }

        throw new HttpResponseException(Redirect::to($model->slug));
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
            $this->redirectToCorrectUrl($value, $model);
        }

        // Extract the ID from the ID-slug format.
        if (preg_match('/^(\d+)-.*/', $value, $matches)) {
            $model = static::findOrFail($matches[1]);

            // If slug doesn't match, redirect to the correct URL.
            if ($value !== $model->slug) {
                $this->redirectToCorrectUrl($value, $model);
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

        return $this->id . '-' . Str::slug($nameWithDashes);
    }
}
