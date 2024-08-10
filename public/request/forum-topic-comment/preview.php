<?php

// TODO migrate to Livewire

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

$normalized = normalize_shortcodes($input['body']);
$sanitized = htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8');

return response()->json([
    'message' => __('legacy.success.ok'),
    'postPreviewHtml' => Blade::render('<x-forum.topic-comment :$variant>{!! $body !!}</x-forum.topic-comment> ', [
        'body' => Shortcode::render($sanitized),
        'variant' => 'preview',
    ]),
]);
