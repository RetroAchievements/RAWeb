<?php
/**
 * exit early - no more feeds in v1
 */
return response()->json(['success' => false]);

$user = requestInputQuery('u');
$friends = requestInputQuery('f');
$count = requestInputQuery('c', 10);
$offset = requestInputQuery('o', 0);

// Sensible caps
if ($count > 100) {
    $count = 100;
}

$type = 'global';

if (isset($user)) {
    if (isset($friends)) {
        $type = 'friends';
    } else {
        $type = 'individual';
    }
}

getFeed($user, $count, $offset, $feedData, 0, $type);

return response()->json($feedData);
