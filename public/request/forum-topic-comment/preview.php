<?php

use App\Site\Enums\Permissions;
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

$parsedPostContent = Shortcode::render($input['body']);

return response()->json([
    'message' => __('legacy.success.ok'),
    'postPreviewHtml' => Blade::render('<x-forum.post :parsedPostContent="$parsedPostContent" isPreview="true" />', [
        'parsedPostContent' => Shortcode::render($input['body']),
    ]),
]);
