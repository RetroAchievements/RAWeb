<?php

use App\Enums\Permissions;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'body' => 'required|string|max:60000',
]);

$mutatedBody = $input['body'];
$mutatedBody = normalize_shortcodes($mutatedBody);
$mutatedBody = str_replace(["\r\n", "\r"], "\n", $mutatedBody); // Convert to Unix newlines.
$mutatedBody = preg_replace('/\n{3,}|(<br\s*\/?>\s*){3,}/i', "\n\n", $mutatedBody); // Handle both \n and <br>.
$mutatedBody = htmlspecialchars($mutatedBody, ENT_QUOTES, 'UTF-8');

return response()->json([
    'message' => __('legacy.success.ok'),
    'postPreviewHtml' => Blade::render('<x-forum.topic-comment :$variant>{!! $body !!}</x-forum.topic-comment> ', [
        'body' => Shortcode::render($mutatedBody),
        'variant' => 'preview',
    ]),
]);
