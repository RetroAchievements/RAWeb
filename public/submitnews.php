<?php
require_once __DIR__ . '/../vendor/autoload.php';

$errorCode = requestInputSanitized('e');
$newsImageInput = requestInputSanitized('g');
$newsArticleID = requestInputSanitized('n', 0, 'integer');

$newsCount = getLatestNewsHeaders(0, 999, $newsData);
$activeNewsArticle = null;

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::SuperUser)) {
    //	Immediate redirect if we cannot validate user!	//TBD: pass args?
    header("Location: " . getenv('APP_URL'));
    exit;
}

RenderHtmlStart();
RenderHtmlHead("Manage News");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<script>
  function onSubmitNews() {
    var title = $('#NewsTitle').val();
    title = replaceAll('http', '_http_', title);

    var payload = $('#NewsPayload').val();
    payload = replaceAll('http', '_http_', payload);

    var imageurl = $('#NewsImage').val();
    imageurl = replaceAll('http', '_http_', imageurl);

    var link = $('#NewsLink').val();
    link = replaceAll('http', '_http_', link);

    var author = $('#NewsAuthor').val();

    //alert( link );

    var posting = $.post('/request/news/update.php', { a: author, p: payload, t: title, l: link, g: imageurl, i: <?php echo $newsArticleID; ?> });
    posting.done(onPostComplete);
    //$("body").find( "#warning" ).html( "Status: Updating..." );
  }

  function onPostComplete(data) {
    if (data !== 'OK') {
      //$("body").find( "#warning" ).html( "Status: Errors..." );
    } else {
      //$("body").find( "#warning" ).html( "Status: Loading..." );
      window.location = '/index.php?e=newspostsuccess';
    }
  }

  function UploadImage() {
    var photo = document.getElementById('uploadimagefile');
    var file = photo.files[0];

    var reader = new FileReader();
    reader.onload = function () {

      $('#loadingicon').fadeTo(100, 1.0);

      $.post('/request/news/image-upload.php', { t: 'NEWS', f: file.name.split('.').pop(), i: reader.result }, onUploadImageComplete);
    };

    reader.readAsDataURL(file);
    return false;
  }

  function onUploadImageComplete(data) {
    $('#loadingicon').fadeTo(100, 0.0);

    if (data.substr(0, 3) == 'OK:') {
      //alert( data );
      $('#NewsImage').val('<?php echo getenv('ASSET_URL') ?>' + data.substr(3));
      $('#NewsImagePreview').attr('src', $('#NewsImage').val());
    } else {
      alert(data);
    }
  }
</script>
<div id="mainpage">
    <div id="fullcontainer">
        <div class="navpath">
            <b>Manage News</b>
        </div>

        <div class="largelist">
            <h2 class="longheader">How to use</h2>
            <p>
                Here you can submit new articles or modify old articles that can be viewed on the frontpage of the site.<br>
                Please note: news images will be scaled to 470px width, and (currently) drawn at 220px height.
            </p>
            <br>
        </div>

        <h2 class="longheader">Submit News</h2>
        <?php
        $yOffs = 0;
        // RenderTwitchTVStream( $yOffs );

        // echo "<div class='right'>";
        // echo "<h2 class='longheader'>Upload news image</h2>";
        // echo "470px max width! Image size will be scaled to fit.<br>";
        //
        // //echo "<form style='padding: 2px;' method='post' enctype='multipart/form-data' >";
        // echo "<input type='submit' name='submit' style='float: right;' value='Select News Image' />";
        // //echo "</form>";
        //
        // echo "<br>";
        // echo "</div>";

        RenderErrorCodeWarning($errorCode);

        echo "Select Existing or Create New:&nbsp;";
        echo "<select name='ab' onchange=\"if (this.selectedIndex >= 0) window.location = '/submitnews.php?n=' + this.value; return false;\" >";

        echo "<option value=0>--New Post--</option>";
        for ($i = 0; $i < $newsCount; $i++) {
            $nextNews = $newsData[$i];
            $nextID = $nextNews['ID'];
            $nextTitle = $nextNews['Title'];

            $selected = ($nextID == $newsArticleID) ? "selected" : "";

            echo "<option value='$nextID' $selected><a href='/submitnews.php?n=$nextID'>$nextID - $nextTitle</a></option>";

            if ($nextNews['ID'] == $newsArticleID) {
                $activeNewsArticle = $nextNews;
            }
        }
        echo "</select><br>";

        if (isset($newsArticleID) && $newsArticleID != 0) {
            echo "ID: <input type='text' name='i' size='2' value='$newsArticleID' readonly><br><br> ";
        }

        $newsTitle = "";
        if (isset($activeNewsArticle)) {
            $newsTitle = $activeNewsArticle['Title'];
        }

        $newsContent = "";
        if (isset($activeNewsArticle)) {
            $newsContent = $activeNewsArticle['Payload'];
        }

        $newsAuthor = $user;
        if (isset($activeNewsArticle)) {
            $newsAuthor = $activeNewsArticle['Author'];
        }

        $newsLink = "";
        if (isset($activeNewsArticle)) {
            $newsLink = $activeNewsArticle['Link'];
        }

        $newsImage = $newsImageInput;
        if (isset($activeNewsArticle)) {
            $newsImage = $activeNewsArticle['Image'];
        }

        echo "<table><tbody>";

        echo "<tr>";
        echo "<td colspan='2'>";
        echo "Title: <input id='NewsTitle' type='text' name='t' style='width: 80%; float: right;' value='$newsTitle' ><br>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2'>";
        echo "Link (optional): <input id='NewsLink' type='text' name='l' style='width: 80%; float: right;' value='$newsLink'><br>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>";
        echo "Image: <input id='NewsImage' size='44' type='text' name='g' value='$newsImage' onchange=\"$('#NewsImagePreview').attr( 'src', $('#NewsImage').val() ); return false;\">";
        echo "</td>";
        echo "<td>";
        echo "&nbsp;<img id='loadingicon' style='opacity: 0;' src='" . getenv('ASSET_URL') . "/Images/loading.gif' alt='loading icon' />";
        echo "&nbsp;New image:";
        echo "<input type='file' style='float: right;' name='file' id='uploadimagefile' onchange=\"return UploadImage();\" /> <br>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2'>";
        echo "Preview: <br>";
        echo "<div class='submitnewstestimageholder'>";
        echo "<img id='NewsImagePreview' src='$newsImage' width='470' style='margin:0;' /><br>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2'>";
        echo "Article Content (most HTML supported): <br>";
        echo "<textarea id='NewsPayload' rows='10' cols='80' name='p' style='width: 100%;'>$newsContent</textarea><br>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2'>";
        echo "<input onclick=\"onSubmitNews()\" type='submit' name='submit' size='37' style='float: right;' value='Submit News Article!' />";
        echo "Author: <input id='NewsAuthor' type='text' name='a' value='$newsAuthor' readonly><br>";
        echo "</td>";
        echo "</tr>";

        echo "</tbody></table>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

