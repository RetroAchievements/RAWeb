<?php

use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Facades\Log;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$userModel = User::firstWhere('User', $user);
if (!$userModel->can('updateAvatar', [User::class])) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    UploadAvatar($user, request()->post('imageData'));
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
