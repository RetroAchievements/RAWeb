<?php
// function WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltipText)
// {
//     $displayable = str_replace("'", '&#39;', $displayable);
//     $tooltipText = str_replace("'", '\\\'', $tooltipText);
//
//     $tooltip = "<div id='objtooltip'>" .
//         "<img src='$tooltipImagePath' width='$tooltipImageSize' height='$tooltipImageSize' />" .
//         "<b>$tooltipTitle</b><br>$tooltipText" .
//         "</div>";
//
//     $tooltip = str_replace('<', '&lt;', $tooltip);
//     $tooltip = str_replace('>', '&gt;', $tooltip);
//
//     return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >$displayable</div>";
// }
