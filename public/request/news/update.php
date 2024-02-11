<?php

use App\Enums\Permissions;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

/** @var User $user */
$user = request()->user();

$input = Validator::validate(Arr::wrap(request()->post()), [
    'news' => 'nullable|integer',
    'body' => 'required|string',
    'title' => 'required|string',
    'link' => 'nullable|url',
    'image' => 'required|url',
]);

/**
 * TODO: Migrate to Markdown. This is a temporary stopgap.
 * Corrects missing quotes in `href` attributes of anchor
 * tags so they don't break the site homepage.
 */
function sanitizeMaybeInvalidHtml(string $htmlContent): string
{
    $pattern = '/<a\s+href=([^\'" >]+)(.*?)>(.*?)<\/a>/i';
    $replacement = '<a href="$1"$2>$3</a>';
    $fixedHtml = preg_replace($pattern, $replacement, $htmlContent);

    // Any tags other than <a> and <br> are considered invalid.
    $allowedTags = '<a><br>';
    $saferHtml = strip_tags($fixedHtml, $allowedTags);

    return $saferHtml;
}

// Sanitize the 'body' field so news managers can't inject any HTML they want.
$input['body'] = sanitizeMaybeInvalidHtml($input['body']);

if (empty($input['body'])) {
    return back()->withErrors(__('legacy.error.invalid_news_content'));
}

$id = (int) $input['news'];

if (empty($id)) {
    /** @var News $news */
    $news = News::create([
        'Title' => $input['title'],
        'Payload' => $input['body'],
        'Author' => $user->User,
        'Link' => $input['link'],
        'Image' => $input['image'],
    ]);

    return redirect(url('/submitnews.php?news=' . $news->ID))->with(['success' => __('legacy.success.create')]);
}

/** @var News $news */
$news = News::findOrFail($id);
$news->update([
    'Title' => $input['title'],
    'Payload' => $input['body'],
    'Link' => $input['link'],
    'Image' => $input['image'],
]);

return back()->with(['success' => __('legacy.success.update')]);
