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

if (empty($input['body'])) {
    return back()->withErrors(__('legacy.error.invalid_news_content'));
}

$id = (int) $input['news'];

if (empty($id)) {
    /** @var News $news */
    $news = News::create([
        'Title' => $input['title'],
        'Payload' => $input['body'],
        'user_id' => $user->id,
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
