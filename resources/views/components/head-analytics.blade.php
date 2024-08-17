{{--
    For Plausible page views, we want to aggregate like-pages into single groups.
    For example, /game/1 and /game/5 should not be considered as separate pages for
    the purpose of page view tracking.
    
    The code below handles this aggregation. Patterns are matched in various URLs,
    and path params are replaced with "_PARAM_".

    Additionally, just so we can drill down into the data if we'd like, the param
    is passed along to Plausible as a custom prop.
--}}

@php
    // Get the current URL path.
    $url = request()->path();

    // Check if the URL should be redacted
    if (preg_match('/\/\d+$/', $url) || preg_match('/\/\d+\//', $url) || preg_match('/^user\/[^\/]+/', $url)) {
        // Redact dynamic segments in the URL.
        $redactedUrl = preg_replace('/\d+/', '_PARAM_', $url);

        // Additionally redact the user routes.
        $redactedUrl = preg_replace('/^user\/[^\/]+/', 'user/_PARAM_', $redactedUrl);
    } else {
        $redactedUrl = "/$url";
    }

    if ($redactedUrl === '//') {
        $redactedUrl = '/';
    }
    if (!str_starts_with($redactedUrl, '/')) {
        $redactedUrl = "/{$redactedUrl}";
    }

    $props = [
        'isAuthenticated' => auth()->check(),
    ];

    // Define regex patterns to extract props from the URL.
    $patterns = [
        '/^system\/(\d+)\//' => 'system',
        '/^game\/(\d+)$/' => 'game',
        '/^achievement\/(\d+)$/' => 'achievement',
        '/^user\/([^\/]+)(\/progress)?$/' => 'user',
        '/^game\/(\d+)\/hashes$/' => 'game',
        '/^ticket\/(\d+)$/' => 'ticket',
    ];

    // Loop through each pattern to extract props.
    foreach ($patterns as $regex => $prop) {
        if (preg_match($regex, $url, $matches)) {
            $props[$prop] = $matches[1];
            break;
        }
    }
@endphp

@if (!app()->environment('local'))
    @if (app()->environment('stage'))
        <script defer data-domain="stage.retroachievements.org" src="https://plausible.retroachievements.org/psa2.js"></script>
    @elseif (app()->environment('production'))
        <script defer data-domain="retroachievements.org" src="https://plausible.retroachievements.org/psa2.js"></script>
    @endif

    <script>
        window.plausible = window.plausible || function() { (window.plausible.q = window.plausible.q || []).push(arguments) }
    </script>

    <script>
        (function() {
            var redactedUrl = "{{ $redactedUrl }}";
            var props = @json($props);

            let searchParams = '';
            if (window.location.search.includes('t=play') || window.location.search.includes('t=develop')) {
                searchParams = window.location.search;
            }

            plausible('pageview', { u: redactedUrl + searchParams, props });
        })();
    </script>
@endif