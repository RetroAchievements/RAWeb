<?php

function avatar(
    $resource,
    $id,
    $label = null,
    $link = null,
    string|bool $tooltip = true,
    $class = 'inline',
    $iconUrl = null,
    $iconSize = 32,
    $iconClass = 'badgeimg',
    ?string $context = null,
    bool $sanitize = true,
    ?string $altText = null,
): string {
    $escapedName = attributeEscape($altText ?? $label);
    if ($sanitize) {
        sanitize_outputs($label);
    }

    if ($iconUrl) {
        $iconLabel = "<img loading='lazy' width='$iconSize' height='$iconSize' src='$iconUrl' alt='$escapedName' class='$iconClass'>";

        $label = $iconLabel . ' ' . $label;
    }

    $tooltipTrigger = '';
    if ($tooltip) {
        $tooltipTrigger = "x-init=\"mobileSafeTipEvents.attachTooltip(\$el, { dynamicType: '$resource', dynamicId: '$id', dynamicContext: '$context' })\"";
        if (is_string($tooltip)) {
            $escapedTooltip = tooltipEscape($tooltip);
            $tooltipTrigger = "x-init=\"mobileSafeTipEvents.attachTooltip(\$el, { staticHtmlContent: useCard('$resource', '$id', '$context', '$escapedTooltip') })\"";
        }
    }

    return "<span class='$class' $tooltipTrigger><a class='inline-block' href='$link'>$label</a></span>";
}

function tooltipEscape(string $input): string
{
    // the Tip() function expects single quotes to be escaped, and other html reserved
    // characters to be converted to entities.
    $input = htmlentities($input, ENT_COMPAT | ENT_HTML401);
    // ENT_COMPAT will not convert single quotes. do so ourself.
    $input = str_replace("'", "\'", $input);

    return str_replace("\n", "<br/>", $input);
}
