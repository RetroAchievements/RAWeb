<?php

return [

    /*
     * If true, player-facing beaten games UI/UX is enabled.
     */
    'beat' => env('FEATURE_BEAT', false),

    /*
     * If true, several slow queries are swapped out for more efficient ones using aggregated data.
     */
    'aggregate_queries' => env('FEATURE_AGGREGATE_QUERIES', false),

];
