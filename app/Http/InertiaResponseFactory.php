<?php

namespace App\Http;

use Illuminate\Contracts\Support\Arrayable;
use Inertia\ResponseFactory;

class InertiaResponseFactory extends ResponseFactory
{
    /**
     * @param array|Arrayable<array-key, mixed> $props
     */
    public function render(string $component, $props = []): InertiaResponse
    {
        if ($props instanceof Arrayable) {
            $props = $props->toArray();
        }

        return new InertiaResponse(
            $component,
            array_merge($this->sharedProps, $props),
            $this->rootView,
            $this->getVersion(),
            $this->encryptHistory ?? config('inertia.history.encrypt', false),
        );
    }
}
