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

$postPreviewHTML = '';
ob_start();
echo "<div class='my-2'>";
echo Blade::render('<x-forum.post :parsedPostContent="$parsedPostContent" isPreview="true" />', [
    'parsedPostContent' => $parsedPostContent,
]);
echo "</div>";
$postPreviewHTML = ob_get_clean();

return response()->json([
    'message' => __('legacy.success.ok'),
    'postPreviewHTML' => $postPreviewHTML,
]);
