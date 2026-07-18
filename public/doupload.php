<?php

use App\Connect\Actions\UnknownRequestAction;
use App\Connect\Actions\UploadBadgeImageAction;
use Sentry\State\Scope;

use function Sentry\configureScope;

$requestType = request()->input('r');

// The upload request type has historically been provided by the filename of the uploaded
// file. If an explicit request type wasn't provided, attempt to extract one.
if (empty($requestType)) {
    $file = request()->file('file');
    $requestType = $file ? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) : '[null]';
}

// Tag the request type so Sentry can group dorequest.php calls by routine.
configureScope(function (Scope $scope) use ($requestType) {
    $scope->setTag('doupload.type', $requestType);
});

$handler = match ($requestType) {
    'uploadbadgeimage' => new UploadBadgeImageAction(),
    default => new UnknownRequestAction(),
};

return $handler->handleRequest(request());
