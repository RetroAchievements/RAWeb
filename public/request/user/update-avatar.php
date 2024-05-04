<?php

use App\Enums\Permissions;
use Illuminate\Support\Facades\Log;

if (!authenticateFromCookie($user, $permissions, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    UploadAvatar($user->username, request()->post('imageData'));
} catch (Exception $exception) {
    $error = $exception->getMessage();
    if ($error == 'Invalid file type' || $error == 'File too large') {
        return response()->json(['message' => $error], 400);
    }

    if (preg_match('/(not a .* file)/i', $exception->getMessage(), $match)) {
        return response()->json(['message' => ucfirst($match[0])], 400);
    }

    Log::error($exception->getMessage());
    abort(500);
}

return response()->json(['message' => __('legacy.success.ok')]);
