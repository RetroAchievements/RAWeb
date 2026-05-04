<?php

return [
    /*
     * Determine if the response cache middleware should be enabled.
     */
    'enabled' => env('RESPONSE_CACHE_ENABLED', false),

    'cache' => [
        /*
         * Here you may define the cache store that should be used to store
         * requests. This can be the name of any store that is configured
         * in your app's cache.php config.
         */
        'store' => env('RESPONSE_CACHE_DRIVER', 'redis'),

        /*
         * The default number of seconds responses will be cached when
         * using the default CacheProfile settings.
         */
        'lifetime_in_seconds' => (int) env('RESPONSE_CACHE_LIFETIME', 180),

        /*
         * If your cache driver supports tags, you may specify a tag name
         * here. All responses will be tagged. When clearing the
         * responsecache only items with that tag will be flushed.
         *
         * You may use a string or an array here.
         */
        'tag' => env('RESPONSE_CACHE_TAG', 'response-cache'),
    ],

    'bypass' => [
        /*
         * The header name that will force a bypass of the cache. This can
         * be useful to monitor the performance of your application without
         * the caching enabled.
         */
        'header_name' => env('CACHE_BYPASS_HEADER_NAME'),

        /*
         * The header value that will force a cache bypass.
         */
        'header_value' => env('CACHE_BYPASS_HEADER_VALUE'),
    ],

    'debug' => [
        /*
         * Determines if debug headers are added to cached responses. This
         * can be handy for debugging how response caching is performing
         * in your app.
         */
        'enabled' => env('APP_DEBUG', false),

        /*
         * The name of the http header containing the point at which the
         * response was cached.
         */
        'cache_time_header_name' => env('RESPONSE_CACHE_HEADER_NAME', 'laravel-responsecache'),

        /*
         * The name of the header for the cache status that indicates
         * whether a response was HIT or MISS.
         */
        'cache_status_header_name' => 'X-Cache-Status',

        /*
         * The header name for the cache age in seconds.
         */
        'cache_age_header_name' => env('RESPONSE_CACHE_AGE_HEADER_NAME', 'laravel-responsecache-age'),

        /*
         * The header name used for the response cache key. This is only
         * added when app.debug is enabled.
         */
        'cache_key_header_name' => 'X-Cache-Key',
    ],

    /*
     * These query parameters will be ignored when generating the cache
     * key. This is useful for ignoring tracking parameters like UTM
     * tags, gclid, and fbclid.
     */
    'ignored_query_parameters' => [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
    ],

    /*
     * The given class will determine if a request should be cached. The
     * default class will cache all successful GET-requests.
     *
     * You can provide your own class given that it implements the
     * CacheProfile interface.
     */
    'cache_profile' => App\Http\ResponseCache\AnonymousCacheProfile::class,

    /*
     * This class is responsible for generating a hash for a request. This
     * hash is used to look up a cached response.
     */
    'hasher' => App\Http\ResponseCache\InertiaAwareHasher::class,

    /*
     * This class is responsible for serializing responses.
     */
    'serializer' => Spatie\ResponseCache\Serializers\JsonSerializer::class,

    /*
     * Here you may define replacers that dynamically replace content from
     * the response. Each replacer must implement the Replacer interface.
     */
    'replacers' => [
        Spatie\ResponseCache\Replacers\CsrfTokenReplacer::class,
    ],
];
