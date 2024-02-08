@props([
    'isMastered' => false,
])

<?php
$insetClassName = $isMastered ? "inset-[7px]" : "inset-[5px]";
$hoverInsetClassName = $isMastered ? "group-hover:inset-[5px]" : "group-hover:inset-[3px]";

$gradientClassNames = $isMastered
    ? "from-yellow-400 to-amber-400 light:from-yellow-700 light:to-amber-700"
    : "from-zinc-400 to-slate-500 light:from-zinc-600 light:to-slate-700";
?>

<div 
    data-testid="progress-blur"
    class="
        absolute {{ $insetClassName }} rounded-lg 
        blur opacity-75 group-hover:opacity-100 {{ $hoverInsetClassName }} transition-all duration-1000 group-hover:duration-200
        bg-gradient-to-r {{ $gradientClassNames }}
        motion-safe:animate-tilt
"></div>