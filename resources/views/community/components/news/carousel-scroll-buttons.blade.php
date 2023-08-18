<?php

$buttonClassNames = <<<EOT
    absolute top-1/2 transition transform -translate-y-1/2 lg:active:scale-95
    text-link bg-black bg-opacity-80 hover:bg-opacity-100
    rounded-full w-10 h-10 flex items-center justify-center
EOT;
?>

<button
    @click="handleScrollButtonClick('previous')"
    aria-label="Go to previous slide"
    class="{{ "btn left-0 top-1/2 translate-x-[-18px] " . $buttonClassNames }}"
>
    <x-pixelarticons-chevron-left class="w-10 h-10"/>
</button>

<button
    @click="handleScrollButtonClick('next')"
    aria-label="Go to next slide"
    class="{{ "btn right-0 top-1/2 translate-x-[18px] " . $buttonClassNames }}"
>
    <x-pixelarticons-chevron-right class="w-10 h-10"/>
</button>
