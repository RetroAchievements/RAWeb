<?php

declare(strict_types=1);

namespace App\Http;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Inertia\ProvidesInertiaProperties;
use Inertia\ResponseFactory;
use InvalidArgumentException;
use UnitEnum;

class InertiaResponseFactory extends ResponseFactory
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'layouts/app';

    /**
     * @param BackedEnum|UnitEnum|string $component
     * @param array<array-key, mixed>|Arrayable<array-key, mixed>|ProvidesInertiaProperties $props
     */
    public function render($component, $props = []): InertiaResponse
    {
        $component = $this->transformComponent($component);

        $component = match (true) {
            $component instanceof BackedEnum => $component->value,
            $component instanceof UnitEnum => $component->name,
            default => $component,
        };

        if (!is_string($component)) {
            throw new InvalidArgumentException('Component argument must be of type string or a string BackedEnum');
        }

        if (config('inertia.pages.ensure_pages_exist', false)) {
            $this->findComponentOrFail($component);
        }

        if ($props instanceof Arrayable) {
            $props = $props->toArray();
        } elseif ($props instanceof ProvidesInertiaProperties) {
            $props = [$props];
        }

        return new InertiaResponse(
            $component,
            $this->sharedProps,
            $props,
            $this->rootView,
            $this->getVersion(),
            $this->encryptHistory ?? config('inertia.history.encrypt', false),
            $this->urlResolver,
        );
    }
}
