<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RA\Permissions;

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$errorCode = requestInputSanitized('e');
$vidID = requestInputSanitized('v', 0, 'integer');
$mobileSetting = requestInputSanitized('m');

RenderHtmlStart();
RenderHtmlHead("RA Cinema");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<script>
  var archiveURLs = [];
  var archiveTitles = [];
  <?php
  $query = "SELECT * 
	FROM PlaylistVideo 
	ORDER BY Added DESC";

  $dbResult = s_mysql_query($query);

  while ($nextData = mysqli_fetch_assoc($dbResult)) {
      //$archiveURLs[ $nextData['ID'] ] = $nextData;
      $nextID = $nextData['ID'];
      $nextURL = $nextData['Link'];
      $nextTitle = htmlspecialchars($nextData['Title']);
      echo "archiveURLs[ $nextID ] = \"$nextURL\";";    //	Push this to JS
      echo "archiveTitles[ $nextID ] = \"$nextTitle\";";
  }
  ?>

  function PostVideoLink() {
    var bodyTag = $('body');
    var title = bodyTag.find('#videourltitle').val();
    var url = bodyTag.find('#videourlinput').val();
    url = replaceAll('http', '_http_', url);

    var posting = $.post('/request/playlist/update.php', { a: '<?php echo $user; ?>', i: <?php echo $vidID; ?>, t: title, l: url });
    posting.done(onPostComplete);
    //$("body").find( "#warning" ).html( "Status: Updating..." );
  }

  function onPostComplete(data) {
    alert(data);
    if (data !== 'OK') {
      //$("body").find( "#warning" ).html( "Status: Errors..." );
    } else {
      //$("body").find( "#warning" ).html( "Status: Loading..." );
      window.location.reload();
    }
  }
</script>
<div id="mainpage">
    <div id="leftcontainer">
        <?php
        //	left
        RenderTwitchTVStream(600, 500, 'left', $vidID);

        if ($mobileSetting == 1) {
            echo "<iframe id='player' type='text/html' width='620' height='378' src='//www.twitch.tv/" . getenv('TWITCH_CHANNEL') . "/hls' frameborder='0'></iframe>";
        }

        if ($permissions >= Permissions::Developer) {
            echo "<div>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Extra (click to show):</span>";
            echo "<div id='devboxcontent'>";

            $vidTitle = "";
            $vidLink = "";
            $vidAuthor = $user;
            $vidAdded = "";
            if ($vidID != 0) {
                $query = "SELECT * 
					FROM PlaylistVideo
					WHERE ID=$vidID";
                $dbResult = s_mysql_query($query);
                $vidData = mysqli_fetch_assoc($dbResult);
                $vidTitle = $vidData['Title'];
                $vidLink = $vidData['Link'];
                $vidAuthor = $vidData['Author'];
                $vidAdded = $vidData['Added'];
            }

            echo "<br>";
            echo "<ul>";
            echo "<li><a href='//www.twitch.tv/" . getenv('TWITCH_CHANNEL') . "/dashboard'>Dashboard on Twitch.tv - select game on this page!</a></li>";
            echo "<li>Add/Modify Video:<br>";
            echo "&nbsp;ID: <input type='text' name='i' size='2' value='$vidID' readonly>&nbsp; ";
            echo "Title: <input id='videourltitle' type='text' name='t' style='width: 50%;' value='$vidTitle' >&nbsp; ";
            echo "&nbsp;Author: <input type='text' name='a' value='$vidAuthor' readonly><br>";
            echo "&nbsp;Link: <input id='videourlinput' type='text' name='l' style='width: 80%;' value='$vidLink'>";
            echo "<input type='submit' name='submit' size='37' style='float: right;' value='Submit!' onclick=\"PostVideoLink()\" />";
            echo "</ul>";

            echo "</div>";
            echo "</div>";
        }
        ?>
    </div>
    <div id="rightcontainer">
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
