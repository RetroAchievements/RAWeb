<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Inertia\PropsResolver;
use Inertia\Response;
use Inertia\Ssr\SsrState;
use Inertia\Support\Header;

class InertiaResponse extends Response
{
    /**
     * Resolve the response and strip null values from the final page props.
     */
    public function toResponse($request)
    {
        $resolver = new PropsResolver($request, $this->component);
        [$resolvedProps, $resolvedMetadata] = $resolver->resolve($this->sharedProps, $this->props);

        $page = array_merge(
            [
                'component' => $this->component,
                'props' => $this->removeNulls($resolvedProps),
                'url' => $this->getUrl($request),
                'version' => $this->version,
            ],
            $resolvedMetadata,
            $this->resolveClearHistory($request),
            $this->resolveEncryptHistory($request),
            $this->resolveFlashData($request),
            $this->resolvePreserveFragment($request),
        );

        if ($request->header(Header::INERTIA)) {
            return new JsonResponse($page, 200, [Header::INERTIA => 'true']);
        }

        App::make(SsrState::class)->setPage($page);

        return ResponseFactory::view($this->rootView, $this->viewData + ['page' => $page]);
    }

    /**
     * Recursively remove null values from an array.
     *
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    protected function removeNulls(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->removeNulls($value);
            } elseif ($value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
