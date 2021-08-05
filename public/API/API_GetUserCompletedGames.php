<?php
/**
 * Public API to return the completion progress data for a given user.
 * This can be used to determine the number of sets a user has completed.
 */
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);

$data = getUsersCompletedGamesAndMax($user);

echo json_encode($data);
