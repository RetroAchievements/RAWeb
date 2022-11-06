<?php

use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
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
