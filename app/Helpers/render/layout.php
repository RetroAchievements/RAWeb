<?php

// see resources/views/layouts/app.blade.php
// see resources/views/layouts/partials/head.blade.php

function RenderContentStart(?string $pageTitle = null): void
{
    // hijack view variables
    view()->share('pageTitle', $pageTitle);

    // TBD add legacy content wrapper start
}

function RenderContentEnd(): void
{
    // TBD add legacy content wrapper end
}

function RenderOpenGraphMetadata(string $title, ?string $OGType, string $imageURL, string $description): void
{
    // hijack view variables
    view()->share('pageTitle', $title);
    view()->share('pageDescription', $description);
    if ($OGType) {
        view()->share('pageType', 'retroachievements:' . $OGType);
    }
    view()->share('pageImage', $imageURL);
}

function RenderPaginator(int $numItems, int $perPage, int $offset, string $urlPrefix): void
{
    // floor to current page
    $offset = floor($offset / $perPage) * $perPage;

    if ($offset > 0) {
        echo "<a title='First' href='{$urlPrefix}0'>&#x226A;</a>&nbsp;";

        $prevOffset = $offset - $perPage;
        echo "<a title='Previous' href='$urlPrefix$prevOffset'>&lt;</a>&nbsp;";
    }

    echo "Page <select class='gameselector' onchange='window.location=\"$urlPrefix\" + this.options[this.selectedIndex].value'>";
    $pages = floor(($numItems + $perPage - 1) / $perPage);
    if ($pages < 1) {
        $pages = 1;
    }
    for ($i = 1; $i <= $pages; $i++) {
        $pageOffset = ($i - 1) * $perPage;
        echo "<option value='$pageOffset'" . (($offset == $pageOffset) ? " selected" : "") . ">$i</option>";
    }
    echo "</select> of $pages";

    $nextOffset = $offset + $perPage;
    if ($nextOffset < $numItems) {
        echo "&nbsp;<a title='Next' href='$urlPrefix$nextOffset'>&gt;</a>";

        $lastOffset = $numItems - 1; // 0-based
        $lastOffset = $lastOffset - ($lastOffset % $perPage);
        echo "&nbsp;<a title='Last' href='$urlPrefix$lastOffset'>&#x226B;</a>";
    }
}
