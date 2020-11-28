<?php
/**
 * Public API to return the completion progress data for a given user.
 * This can be used to determine the number of sets a user has completed.
 */
require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);

// I'm not sure that this identifies the difference between Hardcore mastery vs. completion... A HardcoreMode column is returned though.
// I may want to use getUsersSiteAwards(), but I need to see the output of this to verify what is returned.
$data = getUsersCompletedGamesAndMax($user);

echo json_encode($data);
