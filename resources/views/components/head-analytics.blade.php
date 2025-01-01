{{--
    For Plausible page views, we want to aggregate like-pages into single groups.
    For example, /game/1 and /game/5 should not be considered as separate pages for
    the purpose of page view tracking.
    
    The code below handles this aggregation. Patterns are matched in various URLs,
    and path params are replaced with "_PARAM_".

    Additionally, just so we can drill down into the data if we'd like, the param
    is passed along to Plausible as a custom prop.
--}}

@use('App\Actions\ProcessPlausibleUrlAction')

@php
    $defaultProps = [
        'isAuthenticated' => auth()->check(),
        'scheme' => request()->cookie('scheme') ?: 'dark',
        'theme' => request()->cookie('theme') ?: 'default',
    ];

    $result = (new ProcessPlausibleUrlAction())->execute(
        request()->path(),
        request()->query(),
        $defaultProps,
    );

    $redactedUrl = $result['redactedUrl'];
    $props = $result['props'];
@endphp

@if (app()->environment('local'))
    <script defer data-domain="localhost" src="https://plausible.io/js/script.tagged-events.pageview-props.local.manual.js"></script>
@elseif (app()->environment('stage'))
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
        
        plausible('pageview', { u: redactedUrl, props });
    })();
</script>
