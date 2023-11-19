<?php

declare(strict_types=1);

namespace App\Support\Url;

class UrlBuilder
{
    public static function prettyHttpBuildQuery(array $query): string
    {
        $queryString = http_build_query($query);
        $queryString = str_replace(['%5B', '%5D'], ['[', ']'], $queryString);

        return $queryString;
    }
}
