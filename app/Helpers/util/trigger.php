<?php

use App\Platform\Services\TriggerDecoderService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

function getAchievementPatchReadableHTML(string $mem, array $memNotes): string
{
    $service = new TriggerDecoderService();
    $groups = $service->decode($mem);

    return Blade::render('<x-trigger.viewer :groups="$groups" />',
        ['groups' => $groups]
    );
}

function ValueToTrigger(string $valueDef): string
{
    // if it contains a colon, it's already in a trigger format (i.e. M:0xH001234)
    if (Str::contains($valueDef, ':')) {
        return $valueDef;
    }

    $result = '';

    // regex to change "0xH001234*0.75" to "0xH001234*f0.75"
    $float_replace_pattern = '/(.*)[\*](\d+)\.(.*)/';
    $float_replace_replacement = '${1}*f${2}.${3}';

    // convert max_of elements to alt groups
    $parts = explode('$', $valueDef);
    foreach ($parts as $part) {
        if (count($parts) > 1) {
            $result .= 'S';
        }

        // convert addition chain to AddSource chain with Measured
        $clauses = explode('_', $part);
        $clausesCount = count($clauses);
        for ($i = 0; $i < $clausesCount - 1; $i++) {
            $clause = preg_replace($float_replace_pattern, $float_replace_replacement, $clauses[$i]);
            if (Str::contains($clause, '*-')) {
                $result .= 'B:' . str_replace('*-', '*', $clause) . '_';
            } elseif (Str::contains($clause, '*v-')) {
                $result .= 'B:' . str_replace('*v-', '*', $clause) . '_';
            } else {
                $result .= 'A:' . $clause . '_';
            }
        }

        $clause = preg_replace($float_replace_pattern, $float_replace_replacement, $clauses[count($clauses) - 1]);
        if (Str::contains($clause, '*-')) {
            $result .= 'B:' . str_replace('*-', '*', $clause) . '_M:0';
        } elseif (Str::contains($clause, '*v-')) {
            $result .= 'B:' . str_replace('*v-', '*', $clause) . '_M:0';
        } else {
            $result .= 'M:' . $clause;
        }
    }

    return $result;
}
