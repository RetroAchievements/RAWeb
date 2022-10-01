<?php

use App\Legacy\Models\News;
use App\Legacy\Models\User;
use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

/** @var User $user */
$user = request()->user();

$input = Validator::validate(request()->post(), [
    'news' => 'nullable|integer',
    'body' => 'required|string',
    'title' => 'required|string',
    'link' => 'nullable|url',
    'image' => 'required|url',
]);

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
