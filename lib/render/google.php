<?php

function RenderGoogleTracking()
{
    $googleAnalyticsId = getenv('GOOGLE_ANALYTICS_ID');
    if (!$googleAnalyticsId) {
        return;
    }
    echo <<<END
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=$googleAnalyticsId"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '$googleAnalyticsId');
    </script>
    END;
}
