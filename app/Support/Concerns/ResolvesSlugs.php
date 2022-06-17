<?php

declare(strict_types=1);

namespace App\Support\Concerns;

trait ResolvesSlugs
{
    protected function resolvesToSlug(string $modelSlug, ?string $requestSlug = null): bool
    {
        if ($requestSlug === '-') {
            return false;
        }

        return empty($modelSlug) || $requestSlug === $modelSlug;
    }
}
