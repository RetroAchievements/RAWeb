<?php

use Illuminate\Support\Facades\Log;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    UploadAvatar($user, request()->post('imageData'));
} catch (Exception $ex) {
    $error = $ex->getMessage();
    if ($error == 'Invalid file type' || $error == 'File too large') {
        return response()->json(['message' => $error], 400);
    }

    if (preg_match('/(not a .* file)/i', $ex->getMessage(), $match)) {
        return response()->json(['message' => ucfirst($match[0])], 400);
    }

    Log::error($ex);
    abort(500);
}

return response()->json(['message' => __('legacy.success.ok')]);
