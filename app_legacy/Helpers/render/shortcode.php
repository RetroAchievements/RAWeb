<?php

function RenderShortcodeButtons(): void
{
    echo "<div class='bg-embedded mb-2 p-1'>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[b]\", \"[/b]\")'><b>b</b></span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[i]\", \"[/i]\")'><i>i</i></span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[u]\", \"[/u]\")'><u>u</u></span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[s]\", \"[/s]\")'><s>&nbsp;s&nbsp;</s></span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[code]\", \"[/code]\")'><code>code</code></span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[img=\", \"]\")'>img</span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[url=\", \"]Link[/url]\")'>url</span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[ach=\", \"]\")'>ach</span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[game=\", \"]\")'>game</span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[user=\", \"]\")'>user</span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[spoiler]\", \"[/spoiler]\")'>spoiler</span>";
    echo "<span class='btn btn-link' onclick='injectShortcode(\"[ticket=\", \"]\")'>ticket</span>";
    echo "</div>";
}
