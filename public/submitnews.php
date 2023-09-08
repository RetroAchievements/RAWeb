<?php

use App\Community\Models\News;
use App\Site\Enums\Permissions;
use App\Site\Models\User;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

/** @var User $user */
$user = request()->user();

$newsId = (int) request()->query('news');
$newsItems = News::orderByDesc('ID')->take(500)->get();

/** @var ?News $news */
$news = $newsItems->firstWhere('ID', $newsId);
$newsTitle = $news['Title'] ?? '';
$newsContent = $news['Payload'] ?? '';
$newsAuthor = $news['Author'] ?? $user->User;
$newsLink = $news['Link'] ?? '';
$newsImage = old('image', $news['Image'] ?? '');

RenderContentStart("Manage News");
?>
<article>
    <div class="mb-5">
        <h2 class="longheader">Manage News</h2>
        <div class="embedded grid gap-y-2">
            <p>Here you can submit new articles or modify old articles that can be viewed on the frontpage of the site.</p>

            <div>
                <p>Please note: news images will be slightly darkened and scaled to fit the width of the user's device.</p>
                <p>The images will be drawn at 270px height on desktop and 300px height on mobile.</p>
            </div>
        </div>
    </div>
    <form action="/request/news/update.php" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="news" value="<?= $newsId ?>">
        <table class='table-highlight'>
            <colgroup>
                <col style="width: 150px">
            </colgroup>
            <tbody>
            <tr>
                <td><label for="id">News</label></td>
                <td>
                    <select id="id" name="ab" onchange="if (this.selectedIndex >= 0) window.location = '/submitnews.php?news=' + this.value; return false;">
                        <option value="">--New--</option>
                        <?php foreach ($newsItems as $newsItem): ?>
                            <option value="<?= $newsItem->ID ?>" <?= $newsItem->ID == $newsId ? 'selected' : '' ?>><?= $newsItem->Timestamp->format('Y-m-d H:i') ?> - <?= $newsItem->Title ?></option>
                        <?php endforeach ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="title">Title</label></td>
                <td>
                    <input class="w-full" id="title" type="text" name="title" value="<?= old('title', $newsTitle) ?>">
                </td>
            </tr>
            <tr>
                <td><label for="link">Link</label> (optional)</td>
                <td>
                    <input class="w-full" id="link" type="text" name="link" value="<?= old('link', $newsLink) ?>">
                </td>
            </tr>
            <tr>
                <td class="align-top"><label for="image">Image</label></td>
                <td>
                    <div class="mb-3">
                        <input class="w-full" id="image" type="text" name="image" value="<?= $newsImage ?>"
                               onchange="$('#imagePreview img').attr( 'src', $('#image').val() );$('#imagePreview').show()"
                        >
                    </div>
                    <div id="imagePreview" class="mb-3" style="<?= $newsImage ? '' : 'display:none' ?>">
                        <img src="<?= $newsImage ?>" width="470" alt="News header image preview">
                    </div>
                    <input type="file" name="file" id="uploadimagefile" onchange="return UploadImage();">
                    <img id="loadingicon" style="opacity: 0;" alt="loading icon" src="<?= asset('assets/images/icon/loading.gif') ?>">
                </td>
            </tr>
            <tr>
                <td class="align-top"><label for="body">Content</label></td>
                <td>
                    <textarea class="w-full resize-y min-h-[250px]" id="body" rows="15" name="body"><?= old('body', $newsContent) ?></textarea>
                </td>
            </tr>
            <tr>
                <td><label for="author">Author</label></td>
                <td>
                    <div class="flex justify-between">
                        <div>
                            <input id="author" type="text" value="<?= $newsAuthor ?>" readonly>
                        </div>
                        <button class="btn">Submit</button>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</article>
<script>
function UploadImage() {
    var photo = document.getElementById('uploadimagefile');
    var file = photo.files[0];
    var reader = new FileReader();
    reader.onload = function () {
        $('#loadingicon').fadeTo(100, 1.0);
        $.post('/request/news/update-image.php', { image: reader.result },
            function (data) {
                $('#loadingicon').fadeTo(100, 0.0);
                var image = data.filename;
                $('#image').val(image);
                $('#imagePreview img').attr('src', image);
                $('#imagePreview').show();
            });
    };
    reader.readAsDataURL(file);
    return false;
}
</script>
<?php RenderContentEnd(); ?>
