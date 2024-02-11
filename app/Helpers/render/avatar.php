<?php

/**
 * @deprecated use <x-avatar />
 */
function avatar(
    string $resource,
    int|string $id,
    ?string $label = null,
    ?string $link = null,
    string|bool $tooltip = true,
    string $class = 'inline',
    ?string $iconUrl = null,
    int $iconSize = 32,
    string $iconClass = 'badgeimg',
    ?string $context = null,
    bool $sanitize = true,
    ?string $altText = null,
): string {
    $escapedName = attributeEscape($altText ?? $label);
    if ($sanitize) {
        sanitize_outputs($label);
    }

    if ($iconUrl) {
        $iconLabel = "<img loading='lazy' decoding='async' width='$iconSize' height='$iconSize' src='$iconUrl' alt='$escapedName' class='$iconClass'>";

        $label = $iconLabel . ' ' . $label;
    }

    $tooltipTrigger = '';
    if ($tooltip) {
        $tooltipTrigger = <<<HTML
            x-data="tooltipComponent(\$el, { dynamicType: '$resource', dynamicId: '$id', dynamicContext: '$context' })"
            @mouseover="showTooltip(\$event)"
            @mouseleave="hideTooltip"
            @mousemove="trackMouseMovement(\$event)"
        HTML;

        if (is_string($tooltip)) {
            $escapedTooltip = tooltipEscape($tooltip);
            $tooltipTrigger = <<<HTML
                x-data="tooltipComponent(\$el, { staticHtmlContent: useCard('$resource', '$id', '$context', '$escapedTooltip') })"
                @mouseover="showTooltip(\$event)"
                @mouseleave="hideTooltip"
                @mousemove="trackMouseMovement(\$event)"
            HTML;
        }
    }

    return "<span class='$class' $tooltipTrigger><a class='inline-block' href='$link'>$label</a></span>";
}

function tooltipEscape(string $input): string
{
    // the Tip() function expected single quotes to be escaped, and other html reserved
    // characters to be converted to entities.
    $input = htmlentities($input, ENT_COMPAT | ENT_HTML401);
    // ENT_COMPAT will not convert single quotes. do so ourself.
    $input = str_replace("'", "\'", $input);

    return str_replace("\n", "<br/>", $input);
}
