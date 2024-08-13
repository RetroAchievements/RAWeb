{{--
    For Plausible page views, we want to aggregate like-pages into single groups.
    For example, /game/1 and /game/5 should not be considered as separate pages for
    the purpose of page view tracking.
    
    The script below handles this aggregation. Patterns are matched in various URLs,
    and path params are replaced with "_PARAM_".

    Additionally, just so we can drill down into the data if we'd like, the param
    is passed along to Plausible as a custom prop.
--}}

{{-- 

The JS snippet below is a minified version of the following code:

(function() {
    var url = window.location.pathname;
    var redactedUrl = url.replace(/\/(\d+|[^\/]+)(?=\/|$)/g, function(match, p1) {
        if (/^\d+$/.test(p1) || !/^(system|games|user|progress|hashes)$/.test(p1)) {
            return "/_PARAM_";
        }
        return match;
    });
    var props = {
        isAuthenticated: false, // pull auth()->user() from Laravel here
    };
    var patterns = [
        { regex: /^\/system\/(\d+)\//, prop: 'system' },
        { regex: /^\/game\/(\d+)$/, prop: 'game' },
        { regex: /^\/achievement\/(\d+)$/, prop: 'achievement' },
        { regex: /^\/user\/([^\/]+)(\/progress)?$/, prop: 'user' },
        { regex: /^\/game\/(\d+)\/hashes$/, prop: 'game' }
    ];
    for (var i = 0; i < patterns.length; i++) {
        var match = url.match(patterns[i].regex);
        if (match) {
            props[patterns[i].prop] = match[1];
            break;
        }
    }
    plausible('pageview', { u: redactedUrl + window.location.search, props });
})();

--}}

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
        !function(){for(var e=window.location.pathname,r=e.replace(/\/(\d+|[^\/]+)(?=\/|$)/g,function(e,r){return/^\d+$/.test(r)||!/^(system|games|user|progress|hashes)$/.test(r)?"/_PARAM_":e}),s={isAuthenticated:{{ !!auth()->user() ? 'true' : 'false' }}},a=[{regex:/^\/system\/(\d+)\//,prop:"system"},{regex:/^\/game\/(\d+)$/,prop:"game"},{regex:/^\/achievement\/(\d+)$/,prop:"achievement"},{regex:/^\/user\/([^\/]+)(\/progress)?$/,prop:"user"},{regex:/^\/game\/(\d+)\/hashes$/,prop:"game"}],t=0;t<a.length;t++){var p=e.match(a[t].regex);if(p){s[a[t].prop]=p[1];break}}plausible("pageview",{u:r+window.location.search,props:s})}();
    </script>
@endif
