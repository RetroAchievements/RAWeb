<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'image' => 'required|string',
]);

try {
    $imagePath = UploadNewsImage($input['image']);
} catch (Exception) {
    abort(400);
}

return response()->json([
    'message' => __('legacy.success.ok'),
    'filename' => $imagePath,
]);
