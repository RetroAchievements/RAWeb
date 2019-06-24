<?php
require_once('bootstrap.php');
/////////////////////////////////////////////////////////////////////////////////////////
//    Dynamic Rendering
/////////////////////////////////////////////////////////////////////////////////////////
function getFeedItemTitle($feedData, $withHyperlinks = true, $site = null)
{
    $site = $site ?? getenv('APP_URL');

    $retHTML = '';

    $actType = $feedData['activitytype'];
    $user = $feedData['User'];
    $timestamp = $feedData['timestamp'];
    $achID = $feedData['data']; //    intentional: blind data, dual use
    $achTitle = $feedData['AchTitle'];
    $achPoints = $feedData['AchPoints'];
    $gameID = $feedData['GameID'];
    $gameTitle = $feedData['GameTitle'];
    $console = $feedData['ConsoleName'];

    //    LB only:
    $nextLBID = $feedData['data'];
    $nextLBScore = $feedData['data2'];
    $nextLBName = $feedData['LBTitle'];
    $nextLBFormat = $feedData['LBFormat'];

    //    Inject hyperlinks:
    if ($withHyperlinks) {
        $user = "<a href='$site/User/$user'>$user</a>";
        $achTitle = "<a href='$site/Achievement/$achID'>$achTitle</a>";
        $gameTitle = "<a href='$site/Game/$gameID'>$gameTitle</a>";
        $nextLBName = "<a href='$site/leaderboardinfo.php?i=$nextLBID'>$nextLBName</a>";
    }

    switch ($actType) {
        case 1: // earned achievement
            $retHTML .= "$user earned $achTitle ($achPoints) in $gameTitle";
            break;
        case 2: // login
            $retHTML .= "$user logged in";
            break;
        case 3: // start playing
            $retHTML .= "$user started playing $gameTitle ($console)";
            break;
        case 4: // upload achievement
            $retHTML = "$user uploaded a new achievement: $achTitle ($achPoints) for $gameTitle ($console)";
            break;
        case 5: // edit achievement
            $retHTML = "$user edited $achTitle ($achPoints) for $gameTitle ($console)";
            break;
        case 6: // complete game
            $retHTML = "$user completed $gameTitle ($console)";
            break;
        case 7: // submit new record
            $retHTML = "$user submitted " . GetFormattedLeaderboardEntry($nextLBFormat,
                    $nextLBScore) . " for $nextLBName on $gameTitle ($console)";
            break;
        case 8: // update new record
            $entryType = (strcmp($nextLBFormat, 'TIME') == 0 || strcmp($nextLBFormat,
                    'TIMESECS') == 0) ? "time" : "score";
            $retHTML = "$user improved their $entryType: " . GetFormattedLeaderboardEntry($nextLBFormat,
                    $nextLBScore) . " for $nextLBName on $gameTitle ($console)";
            break;
        case 9: // open ticket
            $retHTML = "$user opened a ticket for $achTitle ($achPoints) in $gameTitle ($console)";
            break;
        case 10: // close ticket
            $retHTML = "$user closed a ticket for $achTitle ($achPoints) in $gameTitle ($console)";
            break;
        case 0:
        default:
            $retHTML = 'Unknown';
    }

    return $retHTML;
}

function getFeedItemHTML($feedData, $user)
{
    $retHTML = '';

    //    WILL ALWAYS BE PRESENT in feedData. Note: in SQL order
    $nextID = $feedData['ID'];
    $nextTimestamp = $feedData['timestamp'];
    $nextActivityType = $feedData['activitytype'];
    $nextUser = $feedData['User'];
    $nextUserPoints = $feedData['RAPoints'];
    $nextUserMotto = $feedData['Motto'];
    $nextData = $feedData['data'];
    $nextData2 = $feedData['data2'];
    $nextGameTitle = $feedData['GameTitle'];
    $nextGameID = $feedData['GameID'];
    $nextGameIcon = $feedData['GameIcon'];
    $nextConsoleName = $feedData['ConsoleName'];
    $nextAchTitle = $feedData['AchTitle'];
    $nextAchDesc = $feedData['AchDesc'];
    $nextAchPoints = $feedData['AchPoints'];
    $nextAchBadge = $feedData['AchBadge'];
    $nextLBName = $feedData['LBTitle'];
    $nextLBDesc = $feedData['LBDesc'];
    $nextLBFormat = $feedData['LBFormat'];

    $rowID = "art_$nextID";
    $dateCell = "<td class='date'><small>&nbsp;" . date("H:i ", $nextTimestamp) . "&nbsp;</small></td>";

    switch ($nextActivityType) {
        case 0: //    misc
            $retHTML .= "<tr id='$rowID' class='feed_login'>";
            $retHTML .= $dateCell;

            $retHTML .= "<td class='icons'>";
            $retHTML .= "</td>";
            $retHTML .= "<td></td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;


        case 1: //    achievement

            $retHTML .= "<tr id='$rowID' class='feed_won'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";
            $retHTML .= GetAchievementAndTooltipDiv($nextData, $nextAchTitle, $nextAchDesc, $nextAchPoints,
                $nextGameTitle, $nextAchBadge, true, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, true);
            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_won'>";
            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, false);
            $retHTML .= " earned ";
            $retHTML .= GetAchievementAndTooltipDiv($nextData, $nextAchTitle, $nextAchDesc, $nextAchPoints,
                $nextGameTitle, $nextAchBadge, false);

            if (isset($nextData2) && $nextData2 == 1) {
                $retHTML .= " (HARDCORE)";
            }

            $retHTML .= " in ";
            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, false, 32,
                true);

            $retHTML .= "</td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";

            break;


        case 2: //    login

            $retHTML .= "<tr id='$rowID' class='feed_login'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";
            $retHTML .= "<img alt='$nextUser logged in' title='Logged in' src='/Images/LoginIcon32.png' width='32' height='32' class='badgeimg' />";
            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, true);
            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_login'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, false);
            $retHTML .= " logged in";

            $retHTML .= "</td>";
            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;


        case 3: //    start playing game

            if ($nextGameTitle !== "UNRECOGNISED") {
                $retHTML .= "<tr id='$rowID' class='feed_startplay'>";
                $retHTML .= $dateCell;

                //    Images:
                $retHTML .= "<td class='icons'>";

                $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, true);
                $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, true);

                $retHTML .= "</td>";

                //    Content:
                $retHTML .= "<td class='feed_startplay'>";

                $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, false);
                $retHTML .= " started playing ";
                $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, false,
                    32, true);

                $retHTML .= "</td>";
                if ($user !== null) {
                    $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
                }
                $retHTML .= "</tr>";
            }
            break;


        case 4: //    upload ach

            $retHTML .= "<tr id='$rowID' class='feed_dev1'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetAchievementAndTooltipDiv($nextData, $nextAchTitle, $nextAchDesc, $nextAchPoints,
                $nextGameTitle, $nextAchBadge, true, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_dev1'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, false);
            $retHTML .= " uploaded a new achievement: ";
            $retHTML .= GetAchievementAndTooltipDiv($nextData, $nextAchTitle, $nextAchDesc, $nextAchPoints,
                $nextGameTitle, $nextAchBadge, false);

            $retHTML .= "</td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;


        case 5: //    modify ach

            $retHTML .= "<tr id='$rowID' class='feed_dev2'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetAchievementAndTooltipDiv($nextData, $nextAchTitle, $nextAchDesc, $nextAchPoints,
                $nextGameTitle, $nextAchBadge, true, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_dev2'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, false);
            $retHTML .= " edited ";
            $retHTML .= GetAchievementAndTooltipDiv($nextData, $nextAchTitle, $nextAchDesc, $nextAchPoints,
                $nextGameTitle, $nextAchBadge, false);

            $retHTML .= "</td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;


        case 6: //    complete game
            $retHTML .= "<tr id='$rowID' class='feed_completegame'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_completegame'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, false);
            if ($nextData2 == 1) {
                $retHTML .= " MASTERED ";
            } else {
                $retHTML .= " completed ";
            }

            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, false, 32,
                true);

            if ($nextData2 == 1) {
                $retHTML .= " (HARDCORE)";
            }

            $retHTML .= "</td>";


            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";

            break;


        case 7: //    submit new record
            $retHTML .= "<tr id='$rowID' class='feed_submitrecord'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_submitrecord'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, false);
            $retHTML .= " submitted ";
            $retHTML .= GetLeaderboardAndTooltipDiv($nextData, $nextLBName, $nextLBDesc, $nextGameTitle, $nextGameIcon,
                GetFormattedLeaderboardEntry($nextLBFormat, $nextData2));
            $retHTML .= " for ";
            $retHTML .= GetLeaderboardAndTooltipDiv($nextData, $nextLBName, $nextLBDesc, $nextGameTitle, $nextGameIcon,
                $nextLBName);
            $retHTML .= " on ";
            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, false, 32,
                true);

            $retHTML .= "</td>";
            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;


        case 8: //    updated record
            $retHTML .= "<tr id='$rowID' class='feed_updaterecord'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_updaterecord'>";

            $entryType = (strcmp($nextLBFormat, 'TIME') == 0 || strcmp($nextLBFormat,
                    'TIMESECS') == 0) ? "time" : "score";

            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, false);
            $retHTML .= " improved their $entryType: ";
            $retHTML .= GetLeaderboardAndTooltipDiv($nextData, $nextLBName, $nextLBDesc, $nextGameTitle, $nextGameIcon,
                GetFormattedLeaderboardEntry($nextLBFormat, $nextData2));
            $retHTML .= " for ";
            $retHTML .= GetLeaderboardAndTooltipDiv($nextData, $nextLBName, $nextLBDesc, $nextGameTitle, $nextGameIcon,
                $nextLBName);
            $retHTML .= " on ";
            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, false, 32,
                true);

            $retHTML .= "</td>";
            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;


        case 9: // open ticket
        case 10: // close ticket
            $retHTML .= "<tr id='$rowID' class='feed_dev2'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetAchievementAndTooltipDiv($nextData, $nextAchTitle, $nextAchDesc, $nextAchPoints,
                $nextGameTitle, $nextAchBadge, true, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_dev2'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, $nextUserPoints, $nextUserMotto, null, null, false);
            $retHTML .= ($nextActivityType == 9 ? " opened " : " closed ") . " a ticket for ";
            $retHTML .= GetAchievementAndTooltipDiv($nextData, $nextAchTitle, $nextAchDesc, $nextAchPoints,
                $nextGameTitle, $nextAchBadge, false);

            $retHTML .= "</td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;
    }

    return $retHTML;
}

function RenderFeedItem($feedData, $user)
{
    echo getFeedItemHTML($feedData, $user);
}

//    23:22 06/04/2013
function RenderFeedComment($user, $comment, $submittedDate)
{
    echo "<tr class='feed_comment'>";

    $niceDate = date("d M\nG:i y ", strtotime($submittedDate));
    //$fullDateHover = date( "d M\nH:i yy", strtotime( $submittedDate ) );

    echo "<td class='smalldate'>$niceDate</td><td class='iconscomment'><a href='/User/$user'><img alt='$user' title='$user' class='badgeimg' src='/UserPic/$user" . ".png' width='32' height='32' /></a></td><td colspan='3'>$comment</td>";

    echo "</tr>";
}

//
function RenderWelcomeComponent()
{
    if (isset($user)) {
        return;
    }

    echo "
    <div class='component welcome'>
        <h2>Welcome!</h2>
        <div id='Welcome'>
            <p>
            Were you the greatest in your day at Mega Drive or SNES games? Wanna prove it? Use our modified emulators and you will be awarded achievements as you play! Your progress will be tracked so you can compete with your friends to complete all your favourite classics to 100%: we provide the emulators for your Windows-based PC, all you need are the roms!<br/>
            <a href='/Game/1'>Click here for an example:</a> which of these do you think you can get?
            </p>
        <br/>
            <p style='clear:both; text-align:center'>
            <a href='/download.php'><b>&gt;&gt;Download an emulator here!&lt;&lt;</b></a><br/>
            </p>
        </div>
    </div>";
}

//
function RenderNewsComponent()
{
    echo "<div class='left'>";
    echo "<h2>News</h2>";
    $numNewsItems = getLatestNewsHeaders(0, 10, $newsHeaders);
    echo "<div id='carouselcontainer' style='height=300px;' >";

    echo "<div id='carousel'>";
    for ($i = 0; $i < $numNewsItems; $i++) {
        RenderNewsHeader($newsHeaders[$i]);
    }
    echo "</div>";

    echo "<a href='#' id='ui-carousel-next'><span>next</span></a>";
    echo "<a href='#' id='ui-carousel-prev'><span>prev</span></a>";

    echo "<div id='carouselpages'></div>";

    echo "</div>";

    echo "</div>";
}

//
function RenderFeedComponent($user)
{
    echo "<div class='left'>";

    echo "<div style='float:right;'>";
    $feedFriendsPrefs = 0;
    if (isset($user)) {
        $feedFriendsPrefs = RA_ReadCookie("RAPrefs_Feed");
        $selGlobal = ($feedFriendsPrefs == 1) ? '' : 'checked';
        $selFriends = ($feedFriendsPrefs == 1) ? 'checked' : '';
        echo "<input type='radio' name='feedpref' $selGlobal onclick=\"refreshFeed(false);\" > Global<br/>";
        echo "<input type='radio' name='feedpref' $selFriends onclick=\"refreshFeed(true);\" > Friends Only";
    }
    echo "</div>";

    echo "<h2 id='globalfeedtitle'>" . ($feedFriendsPrefs ? "Friends Feed" : "Global Feed") . "</h2>";

    echo "<div id='globalfeed'>";
    echo "Here's a breakdown of what's been happening recently:";
    echo "<table id='feed'><tbody>";
    echo "<tr id='feedloadingfirstrow'>";
    echo "<td class='chatcell'>";
    echo "<img src='" . getenv('APP_STATIC_URL') . "/Images/loading.gif' width='16' height='16' alt='loading icon' />";
    echo "</td>";
    echo "<td class='chatcell'>";
    echo "</td>";
    echo "<td class='chatcellmessage'>";
    echo "loading feed...";
    echo "</td>";
    echo "</tr>";
    echo "</tbody></table>";
    echo "</div>";

    echo "</div>";
}

//
function RenderDemoVideosComponent()
{
    $width = '392'; //600px
    $height = $width * (3.0 / 4.0); //'100%'; //400px

    echo "<div id='demo' >";

    echo "<h2>Demos</h2>";

    echo "<h4>Using RAGens</h4>";

    echo "<div class='videocontainer' >";
    echo "<iframe style='border:0;' width='$width' height='$height' src='//www.youtube.com/embed/rKY2mZjurJw' allowfullscreen></iframe>";
    //echo "<iframe src='https://www.youtube-nocookie.com/v/rKY2mZjurJw?hl=en&amp;fs=1' frameborder='0' allowfullscreen></iframe>";
    //echo "<object data='https://www.youtube-nocookie.com/v/rKY2mZjurJw?hl=en&amp;fs=1' style='width:300px;'></object>";
    echo "</div>";

    echo "<h4>Finding Memory Addresses</h4>";

    echo "<div class='videocontainer' >";
    echo "<object type='application/x-shockwave-flash' width='$width' height='$height' data='//www.twitch.tv/widgets/archive_embed_player.swf' id='clip_embed_player_flash' >
        <param name='movie' value='//www.twitch.tv/widgets/archive_embed_player.swf' />
        <param name='allowScriptAccess' value='always' />
        <param name='allowNetworking' value='all' />
        <param name='flashvars' value='auto_play=false&amp;channel=" . getenv('TWITCH_CHANNEL') . "&amp;title=Finding%2BMemory%2BAddresses&amp;chapter_id=2674100&amp;start_volume=25' />
        </object>";
    echo "</div>";

    echo "</div>";
}

//    19:56 06/04/2013
function RenderArticleEmptyComment($articleType, $articleID)
{
    $rowID = "art_$articleID";

    echo "<tr id='$rowID' class='feed_comment'>";

    echo "<td></td><td></td><td></td><td></td><td class='editbutton'><img src='" . getenv('APP_STATIC_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '$articleType' )\" /></td>";

    echo "</tr>";
}

//    19:56 06/04/2013
function RenderArticleComment(
    $articleID,
    $user,
    $points,
    $motto,
    $comment,
    $submittedDate,
    $localUser,
    $articleTypeID,
    $commentID,
    $allowDelete
) {
    $class = '';
    $deleteIcon = '';

    if ($user == $localUser || $allowDelete) {
        $class = 'localuser';

        $img = "<img src='" . getenv('APP_STATIC_URL') . "/Images/cross.png' width='16' height='16' alt='delete comment'/>";
        $deleteIcon = "<div style='float: right;'><a onclick=\"removeComment($articleID, $commentID); return false;\" href='#'>$img</a></div>";
    }

    $artCommentID = "artcomment_" . $articleID . "_" . $commentID;
    echo "<tr class='feed_comment $class' id='$artCommentID'>";

    //$niceDate = date( "d M\nH:i ", $submittedDate );
    $niceDate = date("j M\nG:i Y ", $submittedDate);

    echo "<td alt='Test' class='smalldate'>$niceDate</td>";
    echo "<td class='iconscommentsingle'>" . GetUserAndTooltipDiv($user, $points, $motto, null, null, true) . "</td>";
    echo "<td class='commenttext' colspan='3'>$deleteIcon$comment</td>";

    echo "</tr>";
}

function RenderCommentInputRow($user, $rowIDStr, $artTypeID)
{
    $userImage = "<img alt='$user' title='$user' class='badgeimg' src='/UserPic/" . $user . ".png' width='32' height='32' />";
    $formStr = "<textarea id='commentTextarea' rows=0 cols=30 name='c' maxlength=250></textarea>";
    $formStr .= "&nbsp;";
    $formStr .= "<img id='submitButton' src='" . getenv('APP_STATIC_URL') . "/Images/Submit.png' alt='Submit' style='cursor: pointer;' onclick=\"processComment( '$rowIDStr', '$artTypeID' )\" />";

    echo "<tr id='comment_$rowIDStr'><td></td><td class='iconscommentsingle'>$userImage</td><td colspan='3'>$formStr</td></tr>";
}

function RenderPHPBBIcons()
{
    echo "<div class='buttoncollection'>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectphpbb(\"[b]\", \"[/b]\")'><b>b</b></a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectphpbb(\"[i]\", \"[/i]\")'><i>i</i></a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectphpbb(\"[s]\", \"[/s]\")'><s>&nbsp;s&nbsp;</s></a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectphpbb(\"[img=\", \"]\")'>img</a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectphpbb(\"[url=\", \"]Link[/url]\")'>url</a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectphpbb(\"[ach=\", \"]\")'>ach</a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectphpbb(\"[game=\", \"]\")'>game</a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectphpbb(\"[user=\", \"]\")'>user</a></span>";

    echo "</div>";
}

function RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions = 0)
{
    settype($truePoints, 'integer');
    //    js tooltip code is basically on every page:
    echo "<script type='text/javascript' src='/js/wz_tooltip.js'></script>";

    settype($unreadMessageCount, "integer");

    echo "<div id='topborder'><span id='preload-01'></span><span id='preload-02'></span><span id='preload-03'></span></div>\n";

    echo "<div id='title'>";

    echo "<div id='logocontainer'><a id='logo' href='/'>&nbsp;</a></div>";

    echo "<div class='login'>";

    if ($user == false) {
        echo "<div style='float:right; font-size:75%;'><a href='/resetPassword.php'>Forgot password?</a></div>";
        echo "<b>login</b> to " . getenv('APP_NAME') . ":<br/>";

        echo "<form method='post' action='/login.php'>";
        echo "<div>";
        echo "<input type='hidden' name='r' value='" . $_SERVER['REQUEST_URI'] . "' />";
        echo "<table><tbody>";
        echo "<tr>";
        echo "<td>User:&nbsp;</td>";
        echo "<td><input type='text' name='u' size='16' class='loginbox' value='' /></td>";
        echo "<td></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Pass:&nbsp;</td>";
        echo "<td><input type='password' name='p' size='16' class='loginbox' value='' /></td>";
        echo "<td style='width: 45%'><input type='submit' value='Login' name='submit' class='loginbox' /></td>";
        echo "</tr>";
        echo "</tbody></table>";
        echo "</div>";
        echo "</form>";

        if (!isset($errorCode)) {
            echo "<div class='rightalign'>...or <a href='/createaccount.php'>create a new account</a></div>";
        }

        RenderThemeSelector();
    } else {
        echo "<p>";
        echo "<img src='/UserPic/$user.png' alt='$user' style='float:right' align='right' width='64' height='64' class='userpic' />";

        if ($errorCode == "validatedEmail") {
            echo "Welcome, <a href='/user/$user'>$user</a>!<br/>";
        } else {
            echo "<strong><a href='/user/$user'>$user</a></strong> ($points) <span class='TrueRatio'>($truePoints)</span><br/>";
        }

        echo "<a href='/logout.php?Redir=" . $_SERVER['REQUEST_URI'] . "'>logout</a><br/>";

        $mailboxIcon = $unreadMessageCount > 0 ? getenv('APP_STATIC_URL') . '/Images/_MailUnread.png' : getenv('APP_STATIC_URL') . '/Images/_Mail.png';
        echo "<a href='/inbox.php'>";
        echo "<img id='mailboxicon' style='float:left' src='$mailboxIcon' width='20' height='20'/>";
        echo "&nbsp;";
        echo "(";
        echo "<span id='mailboxcount'>$unreadMessageCount</span>";
        echo ")";
        echo "</a>";

        if ($permissions >= 3) // 3 == Developer
        {
            $openTickets = countOpenTicketsByDev($user);
            if ($openTickets > 0) {
                echo " - <a href='/ticketmanager.php?u=$user'>";
                echo "<font color='red'>Tickets: <strong>$openTickets</strong></font>";
                echo "</a>";
            }
        }

        echo "</p>";

        RenderThemeSelector(); //    Only when logged in
    }

    echo "<br/>";
    echo "</div>";

    echo "</div>";
}

function RenderToolbar($user, $permissions = 0)
{
    echo "<div id='innermenu'>";
    echo "<ul id='menuholder'>";
    echo "<li><a href='#'>Home</a>";
    echo "<ul>";
    echo "<li><a href='/'>Home</a></li>";

    if (isset($user) && $user != "") {
        echo "<li><a href='/User/$user'>$user's Homepage</a></li>";
    }

    // echo "<li><a href='/feed.php?g=1'>Global Feed</a></li>";

    //    SU:
    if ($permissions >= 2) {
        echo "<li><a href='/submitnews.php'>Submit News Article</a></li>";
    }
    //    Admin:
    if ($permissions >= 4) {
        echo "<li><a href='/admin.php'>Admin Tools</a></li>";
    }

    echo "<li><a href='https://docs.retroachievements.org/'>Documentation</a></li>";
    echo "<li><a href='https://docs.retroachievements.org/FAQ/'>- FAQ</a></li>";

    echo "</ul>";
    echo "</li>";

    echo "<li><a href='/download.php'>Download</a>";
    //echo "<ul>";
    //echo "<li><a href='/download.php'>RAGens (Mega Drive)</a></li>";
    //echo "<li><a href='/download.php'>RASnes9x (SNES)</a></li>";
    //echo "</ul>";
    echo "</li>";

    if (isset($user) && $user != "") {
        echo "<li><a href='#'>My Pages</a>";
        echo "<ul>";
        echo "<li><a href='/User/$user'>$user's Homepage</a></li>";
        echo "<li><a href='/feed.php?i=1'>My Feed</a></li>";
        echo "<li><a href='/feed.php'>Friends Feed</a></li>";
        echo "<li><a href='/friends.php'>Friends List</a></li>";
        echo "<li><a href='/achievementList.php?s=4&p=2'>Easy Achievements</a></li>";
        echo "<li><a href='/achievementList.php?s=14&p=1'>My Best Awards</a></li>";
        echo "<li><a href='/history.php'>My Legacy</a></li>";
        echo "<li><a href='/logout.php'>Log Out</a></li>";
        echo "</ul>";
        echo "</li>";
    } else {
        echo "<li><a href='/createaccount.php'>Create Account</a>";
        echo "</li>";
    }

    if (isset($user) && $user != "") {
        echo "<li><a href='#'>Messages</a>";
        echo "<ul>";
        echo "<li><a href='/inbox.php'>Inbox</a></li>";
        echo "<li><a href='/createmessage.php'>New Message</a></li>";
        //echo "<li><a href='/sentitems.php'>Sent Items</a></li>";
        //echo "<li><a href='/inbox.aspx?deleted=1'>Deleted Items</a></li>";
        //echo "<li><a href='/archive.aspx'>Archived Inbox</a></li>";
        //echo "<li><a href='/archivesent.aspx'>Archived Sent Items</a></li>";
        echo "</ul>";
        echo "</li>";
    }

    echo "<li><a href='#'>Forums</a>";
    echo "<ul>";
    echo "<li><a href='/forum.php'>Forums</a></li>";
    echo "<li><a href='/forum.php?c=1'>- Community</a></li>";
    echo "<li><a href='/viewforum.php?f=25'>+- Competitions</a></li>";
    echo "<li><a href='/forum.php?c=7'>- Developers</a></li>";
    echo "<li><a href='/forum.php?c=2'>- Mega Drive/Genesis</a></li>";
    echo "<li><a href='/forum.php?c=6'>- SNES</a></li>";
    echo "<li><a href='/forum.php?c=8'>- Gameboy/GBA</a></li>";
    echo "<li><a href='/forum.php?c=9'>- NES</a></li>";
    echo "<li><a href='/forum.php?c=10'>- PC Engine</a></li>";
    echo "<li><a href='/largechat.php'>Chat/RA Cinema</a></li>";
    echo "<li><a href='#' onclick=\"window.open('" . getenv('APP_URL') . "/popoutchat.php', 'chat', 'status=no,height=560,width=340'); return false;\">Pop-out Chat</a></li>";

    echo "<li><a href='/forumposthistory.php'>Recent Posts</a></li>";
    echo "</ul>";
    echo "</li>";

    echo "<li><a href='#'>Site Pages</a>";
    echo "<ul>";
    echo "<li><a href='/popularGames.php'>Popular Games</a></li>";
    echo "<li><a href='/gameList.php'>Supported Games</a></li>";
    echo "<li><a href='/gameList.php?c=1'>- Mega Drive/Genesis</a></li>";
    echo "<li><a href='/gameList.php?c=11'>- Master System</a></li>";
    echo "<li><a href='/gameList.php?c=33'>- SG-1000</a></li>";
    echo "<li><a href='/gameList.php?c=15'>- Game Gear</a></li>";
    echo "<li><a href='/gameList.php?c=3'>- Super Nintendo</a></li>";
    echo "<li><a href='/gameList.php?c=4'>- Gameboy</a></li>";
    echo "<li><a href='/gameList.php?c=6'>- Gameboy Color</a></li>";
    echo "<li><a href='/gameList.php?c=5'>- Gameboy Advance</a></li>";
    echo "<li><a href='/gameList.php?c=7'>- NES</a></li>";
    echo "<li><a href='/gameList.php?c=2'>- N64</a></li>";
    echo "<li><a href='/gameList.php?c=28'>- Virtual Boy</a></li>";
    echo "<li><a href='/gameList.php?c=8'>- PC Engine</a></li>";
    //echo "<li><a href='/gameList.php?c=12'>- PS1</a></li>";
    echo "<li><a href='/gameList.php?c=14'>- Neo Geo Pocket</a></li>";
    echo "<li><a href='/gameList.php?c=25'>- Atari 2600</a></li>";
    echo "<li><a href='/gameList.php?c=51'>- Atari 7800</a></li>";
    echo "<li><a href='/gameList.php?c=13'>- Atari Lynx</a></li>";
    echo "<li><a href='/gameList.php?c=44'>- ColecoVision</a></li>";
    echo "<li><a href='/gameList.php?c=27'>- Arcade</a></li>";
    echo "<li><a href='/gameList.php?c=47'>- PC-8000/8800</a></li>";
    echo "<li><a href='/gameList.php?c=38'>- Apple II</a></li>";
    echo "<li><a href='/awardedList.php'>Commonly Won Achievements</a></li>";
    echo "<li><a href='/gameSearch.php?p=0'>Hardest Achievements</a></li>";
    echo "<li><a href='/userList.php'>User List</a></li>";
    echo "<li><a href='/achievementList.php'>Achievements List</a></li>";
    echo "<li><a href='/leaderboardList.php'>Leaderboards List</a></li>";
    echo "<li><a href='/developerstats.php'>Developer Stats</a></li>";
    echo "<li><a href='/searchresults.php'>Search the site</a></li>";
    echo "<li><a href='https://github.com/RetroAchievements/'>Source Code</a></li>";
    echo "<li><a href='/APIDemo.php'>Web API</a></li>";
    echo "<li><a href='/rss.php'>RSS Feeds</a></li>";
    echo "</ul>";
    //echo "</li>";
    //echo "<li><a href='/leaderboards.aspx'>Statistics</a>";
    //echo "<ul>";
    //echo "<li><a href='/siteleaderboards.aspx'>Site Leaderboards</a></li>";
    //echo "<li><a href='/userleaderboards.aspx'>User Leaderboards</a></li>";
    //echo "</ul>";
    echo "</li>";

    if ($permissions >= 3) {
        echo "<li><a href='#'>Developers</a>";
        echo "<ul>";
        echo "<li><a href='/developerstats.php'>Developer Stats</a></li>";
        echo "<li><a href='/achievementinspector.php'>Ach. Inspector</a></li>";
        echo "<li><a href='/ticketmanager.php'>Ticket Manager</a></li>";
        echo "<li><a href='/ticketmanager.php?f=1'>Most Reported Games</a></li>";
        echo "<li><a href='/viewforum.php?f=0'>Invalid Forum Posts</a></li>";
        echo "<li><a href='/viewtopic.php?t=394'>Official To-Do List</a></li>";
        echo "</ul>";

        echo "</li>";
    }

    if (isset($user) && $user != "") {
        echo "<li><a href='#'>Settings</a>";
        echo "<ul>";
        echo "<li><a href='/controlpanel.php'>My Settings</a></li>";
        //echo "<li><a href='/customizehomepage.php'>My Homepage</a></li>";
        //echo "<li><a href='/facebook.php'>Facebook Settings</a></li>";
        //echo "<li><a href='/changeemail.php'>Change Email Address</a></li>";
        //echo "<li><a href='/changepassword.php'>Change Password</a></li>";
        echo "</ul>";
        echo "</li>";
    }
    //echo "<li><a href='/news.aspx'>Useful Links</a>";
    //echo "<ul>";
    //echo "<li><a href='/gopro.aspx' title='Upgrade to Pro account'>Pro account</a></li>";
    //echo "</ul>";
    //echo "</li>";
    echo "</ul>";

    echo "<form action='/searchresults.php' method='get'>";
    echo "<div class='searchbox'>";
    //echo "Search:&nbsp;";
    echo "<input size='24' name='s' type='text' class='searchboxinput' />";
    echo "&nbsp;";
    echo "<input type='submit' value='Search' />";
    echo "</div>";
    echo "</form>";

    echo "</div>";
    echo "<div style='clear:both;'></div>"; //    Makes it work with mobile browsers :)
}

function RenderFooter()
{
    echo "<div style='clear:all;'></div>";

    echo "<div id='footer'>";

    //    Inject fb like onto every page! muhahaha
    //echo "<div class='fb-like' style='float:left'></div>";

    echo "<div class='footericonset'>";

    //    W3
    // echo "<div class='footericon' >";
    //echo "<p about='' resource='http://www.w3.org/TR/rdfa-syntax' rel='dc:conformsTo' xmlns:dc='http://purl.org/dc/terms/'>";
    // echo "<p>";
    // echo "<a href='https://validator.w3.org/check?uri=referer'><img src='https://www.w3.org/Icons/valid-xhtml-rdfa' alt='Valid XHTML + RDFa' height='31' width='88' /></a>";
    // echo "</p>";
    // echo "</div>";

    //    My TM
    echo "<div class='footertext'>";
    echo "<p style='font-size: x-small;'>";
    echo "Content by <small><a href='http://www.immensegames.com' target='_blank' rel='noopener'>Immense Games</a></small><br/>";
    //echo "<small>Last Updated July 2013</small>";
    global $g_numQueries;
    global $g_pageLoadAt;
    $loadDuration = microtime(true) - $g_pageLoadAt;
    echo "Generated from $g_numQueries queries in " . sprintf('%1.3f', ($loadDuration)) . " seconds";

    if ($loadDuration > 2.4) {
        error_log(CurrentPageURL() . " - took " . sprintf('%1.3f', $loadDuration) . " to fetch!");
    }
    echo "</p>";

    echo "</div>";

    echo "</div>";

    echo "</div>";
}

function RenderFBLoginPrompt()
{
    //echo "<div id='fb-root'></div><script type='text/javascript'>(function(d, s, id) {var js, fjs = d.getElementsByTagName(s)[0];if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = \"//connect.facebook.net/en_GB/all.js#xfbml=1&appId=490904194261313\";  fjs.parentNode.insertBefore(js, fjs);}(document, 'script', 'facebook-jssdk'));</script>";
    echo "<div class='fb-login-button' scope='publish_stream'>Login with Facebook</div>";
}

function RenderFBLogoutPrompt()
{
    global $fbConn;
    printf("<a href='%s'>(logout)</a>", $fbConn->getLogoutUrl());
    //echo "<div id=\"fb-root\"></div><script type='text/javascript'>(function(d, s, id) {var js, fjs = d.getElementsByTagName(s)[0];if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = \"//connect.facebook.net/en_GB/all.js#xfbml=1&appId=490904194261313\";  fjs.parentNode.insertBefore(js, fjs);}(document, 'script', 'facebook-jssdk'));</script>";
    //echo "<div class=\"fb-login-button\" scope=\"publish_stream\">Login with Facebook</div>";
}

function RenderFBDialog($fbUser, &$fbRealNameOut, &$fbURLOut, $user)
{
    $fbRealNameOut = "";
    $fbURLOut = "";

    try {
        global $fbConn;
        global $fbConfig;
        //$access_token =
        //$access_token = '490904194261313|WGR9vR4fulyLxEufSRH2CJrthHw';
        //$attemptLogin = FALSE;

        if ($fbUser == 0) {
            //echo "req. associate!<br/>";
            ////    Attempt associate?
            //$message = "/me/?access_token=$access_token";
            //$ret_obj = $fbConn->api($message,'GET');
            //if( $ret_obj )
            //{
            //echo "found 'me'!<br/>";
            //$fbID = $ret_obj['id'];
            //if( $fbID !== 0 && $fbUser == 0 )
            //{
            //    error_log( __FUNCTION__ . " warning: inconsistency found $fbID $fbUser" );
            //    echo "inconsistency found $fbID $fbUser<br/>";
            //    //    DB inconsistency: update our records!
            //    if( associateFB( $user, $fbID ) )
            //    {
            //        error_log( __FUNCTION__ . " warning: associated $user, $fbID" );
            //        echo "associate OK!<br/>";
            //        $fbUser = $fbID;
            //    }
            //    else
            //    {
            //        RenderFBLoginPrompt();
            //    }
            //}
            //else
            //{
            //        RenderFBLoginPrompt();
            //}
            //}
            //else
            //{
            RenderFBLoginPrompt();
            //}
        }

        if ($fbUser !== 0) {
            $message = "/$fbUser/?access_token=" . $fbConfig['appToken'];
            //echo "<br/>DEBUG:<br/>" . $message . "<br/>";
            $ret_obj = $fbConn->api($message, 'GET');
            if ($ret_obj) {
                $fbRealNameOut = $ret_obj['name'];
                $fbURLOut = $ret_obj['link'];
                //print_r( $ret_obj );
                return true;
            }
        }
    } catch (FacebookApiException $e) {
        error_log("Facebook API Exception " . $e->getType());
        error_log("Facebook API Exception Msg " . $e->getMessage());
        error_log(__FUNCTION__ . " catch: input $fbUser");
        RenderFBLoginPrompt();
    }

    return false;
}

function RenderNewsHeader($newsData)
{
    $dataID = $newsData['ID'];
    $title = $newsData['Title'];
    $payload = $newsData['Payload'];
    $image = $newsData['Image'];

    $link = htmlspecialchars($newsData['Link']);


    $author = $newsData['Author'];
    $authorLink = GetUserAndTooltipDiv($author, null, null, null, null, false);
    $timestampStr = date("d M", $newsData['TimePosted']);
    $niceDate = getNiceDate($newsData['TimePosted']);

    //if( isset( $link ) )
    //else
    //    echo "<h4>$title</h4>";

    $zPos = $dataID;
    $zPos2 = $dataID + 10;

    echo "<div class='newsbluroverlay'>";
    echo "<div>";

    //echo "<span id='NEWSIMG_" . $dataID . "' class='newsimage'><img style='position: absolute; right: 0px; top:0px; width: 100%; z-index:$zPos; opacity: 0.4' src='$image' align='right' /></span>";
    //    BG
    echo "<div class='newscontainer' style='background: url(\"$image\") repeat scroll; z-index:$zPos; opacity:0.5; width: 470px; height:222px; background-size: 100% auto;' >";
    echo "</div>";

    echo "<div class='news' >";

    //    Title
    echo "<h4 style='z-index:$zPos2; position: absolute; width: 460px; top:2px; left:10px; white-space: nowrap;' ><a class='newstitle shadowoutline' href='$link'>$title</a></h4>";

    //    Text
    //echo "<small>[" . $timestampStr . "] </small>";
    echo "<div class='newstext shadowoutline' style='z-index:$zPos2; position: absolute; width: 90%; top: 40px; left:10px;'>$payload</div>";

    //    Author
    echo "<div class='newsauthor shadowoutline' style='z-index:$zPos2; position: absolute; width: 470px; top: 196px; left:0px; text-align: right'>~~$authorLink, $niceDate</div>";

    echo "</div>";
    //echo "<div style='clear:both'></div>";

    echo "</div>";
    echo "</div>";
}

function RenderCommentsComponent(
    $user,
    $numComments,
    $commentData,
    $articleID,
    $articleTypeID,
    $forceAllowDeleteComments
) {
    $userID = getUserIDFromUser($user);

    echo "<div class='commentscomponent'>";

    if ($numComments == 0) {
        echo "No comments yet. Will you be the first?<br/>";
    } else {
        echo "Recent comment(s):<br/>";
    }

    echo "<table id='feed'><tbody>";

    $lastID = 0;
    $lastKnownDate = 'Init';

    for ($i = 0; $i < $numComments; $i++) {
        $nextTime = $commentData[$i]['Submitted'];

        $dow = date("d/m", $nextTime);
        if ($lastKnownDate == 'Init') {
            $lastKnownDate = $dow;
            //echo "<tr><td class='date'>$dow:</td></tr>";
        } elseif ($lastKnownDate !== $dow) {
            $lastKnownDate = $dow;
            //echo "<tr><td class='date'><br/>$dow:</td></tr>";
        }

        if ($lastID != $commentData[$i]['ID']) {
            $lastID = $commentData[$i]['ID'];
        }

        $canDeleteComments = ($articleTypeID == 3) && ($userID == $articleID);
        $canDeleteComments |= $forceAllowDeleteComments;

        RenderArticleComment($articleID, $commentData[$i]['User'], $commentData[$i]['RAPoints'],
            $commentData[$i]['Motto'], $commentData[$i]['CommentPayload'], $commentData[$i]['Submitted'], $user,
            $articleTypeID, $commentData[$i]['ID'], $canDeleteComments);
    }

    if (isset($user)) {
        //    User comment input:
        $commentInputBoxID = 'art_' . $articleID;
        RenderCommentInputRow($user, $commentInputBoxID, $articleTypeID);
    }

    echo "</tbody></table>";
    echo "<br/>";

    echo "</div>";
}

function RenderTopicCommentPayload($payload)
{
    //    TBD: interpret phpbb syntax and reinterpret as good HTML :)

    $payload = parseTopicCommentPHPBB($payload);

    $formattedPayload = str_replace("\n", "<br/>", $payload);
    echo $formattedPayload;
}

function RenderErrorCodeWarning($location, $errorCode)
{
    if (isset($errorCode)) {
        echo "<div class=$location>";
        echo "<h2>Information</h2>";

        if ($errorCode == "validatedEmail") {
            echo "<div id='warning'>Email validated!</div>";
        } elseif ($errorCode == "validateEmailPlease") {
            echo "<div id='warning'>An email has been sent to the email address you supplied. Please click the link in that email.</div>";
        } elseif ($errorCode == "incorrectpassword") {
            echo "<div id='warning'>Incorrect User/Password! Please re-enter.</div>";
        } elseif ($errorCode == "accountissue") {
            echo "<div id='warning'>There appears to be a problem with your account. Please contact the administrator <a href='" . getenv('APP_URL') . "/user/RAdmin'>here</a> for more details.</div>";
        } elseif ($errorCode == "notloggedin") {
            echo "<div id='warning'>Please log in.</div>";
        } elseif ($errorCode == "resetok") {
            echo "<div id='warning'>Reset was performed OK!</div>";
        } elseif ($errorCode == "resetfailed") {
            echo "<div id='warning'>Problems encountered while performing reset. Do you have any achievements to reset?</div>";
        } elseif ($errorCode == "modify_game_ok") {
            echo "<div id='warning'>Game modify successful!</div>";
        } elseif ($errorCode == "errors_in_modify_game") {
            echo "<div id='warning'>Problems encountered while performing modification. Does the target game already exist? If so, try a merge instead on the target game title.</div>";
        } elseif ($errorCode == "merge_success") {
            echo "<div id='warning'>Game merge successful!</div>";
        } elseif ($errorCode == "merge_failed") {
            echo "<div id='warning'>Problems encountered while performing merge. These errors have been reported and will be fixed soon... sorry!</div>";
        } elseif ($errorCode == "recalc_ok") {
            echo "<div id='warning'>Score recalculated! Your new score is shown at the top-right next to your avatar.</div>";
        } elseif ($errorCode == 'changeerror') {
            echo "<div id='warning'>Warning: An error was encountered. Please check and try again.</div>";
        } elseif ($errorCode == 'changeok') {
            echo "<div id='warning'>Info: Change(s) made successfully!</div>";
        } elseif ($errorCode == 'newspostsuccess') {
            echo "<div id='warning'>Info: News post added/updated successfully!</div>";
        } elseif ($errorCode == 'newspostfail') {
            echo "<div id='warning'>Warning! Post not made successfully. Do you have correct permissions?</div>";
        } elseif ($errorCode == 'uploadok') {
            echo "<div id='warning'>Info: Image upload OK!</div>";
        } elseif ($errorCode == 'modify_ok') {
            echo "<div id='warning'>Info: Modified OK!</div>";
        } elseif ($errorCode == 'sentok') {
            echo "<div id='warning'>Info: Message sent OK!</div>";
        } elseif ($errorCode == 'deleteok') {
            echo "<div id='warning'>Info: Message deleted OK!</div>";
        } elseif ($errorCode == 'success') {
            echo "<div id='warning'>Info: Successful!</div>";
        } elseif ($errorCode == 'delete_ok') {
            echo "<div id='warning'>Info: Deleted OK!</div>";
        } elseif ($errorCode == 'badcredentials') {
            echo "<div id='warning'>There appears to be a problem with your account. Please contact <a href='" . getenv('APP_URL') . "/user/RAdmin'>RAdmin</a> for more details.</div>";
        } elseif ($errorCode == 'friendadded') {
            echo "<div id='warning'>Friend Added!</div>";
        } elseif ($errorCode == 'friendconfirmed') {
            echo "<div id='warning'>Friend Confirmed!</div>";
        } elseif ($errorCode == 'friendrequested') {
            echo "<div id='warning'>Friend Request sent!</div>";
        } elseif ($errorCode == 'friendremoved') {
            echo "<div id='warning'>Friend Removed.</div>";
        } elseif ($errorCode == 'friendblocked') {
            echo "<div id='warning'>User blocked.</div>";
        } elseif ($errorCode == 'userunblocked') {
            echo "<div id='warning'>User unblocked.</div>";
        } elseif ($errorCode == 'newadded') {
            echo "<div id='warning'>Friend request sent.</div>";
        } elseif ($errorCode == 'OK' || $errorCode == 'ok') {
            echo "<div id='warning'>Performed OK!</div>";
        } elseif ($errorCode == 'badpermissions') {
            echo "<div id='warning'>You don't have permission to view this page! If this is incorrect, please leave a message in the forums.</div>";
        } elseif ($errorCode == 'nopermission') {
            echo "<div id='warning'>You don't have permission to view this page! If this is incorrect, please leave a message in the forums.</div>";
        } elseif ($errorCode == 'checkyouremail') {
            echo "<div id='warning'>Please check your email for further instructions.</div>";
        } elseif ($errorCode == 'newpasswordset') {
            echo "<div id='warning'>New password accepted. Please log in.</div>";
        } elseif ($errorCode == 'issue_submitted') {
            echo "<div id='warning'>Your issue ticket has been successfully submitted.</div>";
        } elseif ($errorCode == 'issue_failed') {
            echo "<div id='warning'>Sorry. There was an issue submitting your ticket.</div>";
        }

        echo "</div>";
    }
}

function RenderLoginComponent($user, $points, $errorCode, $inline = false)
{
    if ($inline == true) {
        echo "<div class='both'><div class=''>";
    } else {
        echo "<div class=''><div class=''>";
    }

    if ($user == false) {
        echo "<h3>login</h3>";
        echo "<div class='infobox'>";
        echo "<b>login</b> to " . getenv('APP_NAME') . ":<br/>";

        echo "<form method='post' action='/login.php'>";
        echo "<div>";
        echo "<input type='hidden' name='r' value='" . $_SERVER['REQUEST_URI'] . "' />";
        echo "<table style='logintable'><tbody>";
        echo "<tr>";
        echo "<td style='loginfieldscell'>User:&nbsp;</td>";
        echo "<td style='loginfieldscell'><input type='text' name='u' size='16' class='loginbox' value='' /></td>";
        echo "<td></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td style='loginfieldscell'>Pass:&nbsp;</td>";
        echo "<td style='loginfieldscell'><input type='password' name='p' size='16' class='loginbox' value='' /></td>";
        echo "<td style='loginbuttoncell'><input type='submit' value='Login' name='submit' class='loginbox' /></td>";
        echo "</tr>";
        echo "</tbody></table>";
        echo "</div>";
        echo "</form>";

        echo "or <a href='/createaccount.php'>create a new account</a><br/>";

        echo "</div>";
    } else {
        echo "<h3>$user</h3>";
        echo "<div class='infobox'>";

        echo "<p>";
        echo "<img class='userpic' src='/UserPic/$user.png' alt='$user' style='float:right' align='right' width='128' height='128' />";

        if ($errorCode == "validatedEmail") {
            echo "Welcome, <a href='/user/$user'>$user</a>!<br/>";
        } else {
            echo "<strong><a href='/user/$user'>$user</a></strong> ($points)<br/>";
        }

        echo "<a href='/logout.php?Redir=" . $_SERVER['REQUEST_URI'] . "'>logout</a><br/>";

        echo "</p>";

        echo "</div>";
    }
    echo "<br/>";
    echo "</div>";
    echo "</div>";
}

function RenderScoreLeaderboardComponent($user, $points, $friendsOnly, $numToFetch = 10)
{
    $count = getTopUsersByScore($numToFetch, $dataArray, ($friendsOnly == true) ? $user : null);

    echo "<div id='leaderboard' class='component' >";

    if ($friendsOnly == true) {
        echo "<h3>Friends Leaderboard</h3>";
        if ($count == 0) {
            echo "You don't appear to have friends registered here yet. Why not leave a comment on the <a href='/forum.php'>forums</a> or <a href='/userList.php'>browse the user pages</a> to find someone to add to your friend list?<br/>";
        }
    } else {
        echo "<h3>Global Leaderboard</h3>";
    }

    $userRank = ($user !== null) ? $userRank = getUserRank($user) : 0;

    echo "<table><tbody>";
    echo "<tr><th>Rank</th><th colspan='2'>User</th><th>Points</th></tr>";

    for ($i = 0; $i < $count; $i++) {
        if (!isset($dataArray[$i])) {
            continue;
        }

        $nextUser = $dataArray[$i][1];
        $nextPoints = $dataArray[$i][2];
        $nextTruePoints = $dataArray[$i][3];

        echo "<tr>";
        echo "<td class='rank'>" . ($i + 1) . "</td>";
        echo "<td class='userimage'><img alt='$nextUser' title='$nextUser' src='/UserPic/$nextUser.png' width='32' height='32' /></td>";
        echo "<td class='user'><div class='fixedsize'><a href='/User/$nextUser'>$nextUser</a></div></td>";
        echo "<td class='points'>$nextPoints<span class='TrueRatio'>  ($nextTruePoints)</span></td>";
        echo "</tr>";
    }
    if ($user !== null && $friendsOnly == false) {
        echo "<tr>";
        echo "<td class='rank'> $userRank </td>";
        echo "<td><img alt='$user' title='$user' src='/UserPic/$user.png' width='32' height='32' /></td>";
        echo "<td class='user'><a href='/User/$user'>$user</a></td>";
        echo "<td class='points'>$points</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

    if ($friendsOnly == false) {
        echo "<span class='morebutton'><a href='/userList.php?s=2'>more...</a></span>";
    } else {
        echo "<span class='morebutton'><a href='/friends.php'>more...</a></span>";
    }

    echo "</div>";
}

function RenderTopAchieversComponent($gameTopAchievers)
{
    $numItems = count($gameTopAchievers);

    echo "<div id='leaderboard' class='component' >";

    echo "<h3>High Scores</h3>";

    echo "<table class='smalltable'><tbody>";
    echo "<tr><th>Pos</th><th colspan='2' style='max-width:30%'>User</th><th>Points</th></tr>";

    for ($i = 0; $i < $numItems; $i++) {
        if (!isset($gameTopAchievers[$i])) {
            continue;
        }

        $nextUser = $gameTopAchievers[$i]['User'];
        $nextPoints = $gameTopAchievers[$i]['TotalScore'];
        $nextLastAward = $gameTopAchievers[$i]['LastAward'];

        //    Alternating colours for table :)
        if ($i % 2 == 0) {
            echo "<tr>";
        } else {
            echo "<tr class='alt'>";
        }

        echo "<td class='rank'>";
        echo $i + 1;
        echo "</td>";

        echo "<td>";
        echo GetUserAndTooltipDiv($nextUser, null, null, null, null, true);
        echo "</td>";

        echo "<td class='user'>";
        echo GetUserAndTooltipDiv($nextUser, null, null, null, null, false);
        echo "</td>";

        echo "<td class='points'>";
        echo "<span class='hoverable' title='Latest awarded at $nextLastAward'>$nextPoints</span>";
        echo "</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "</div>";
}

function RenderSiteAwards($userAwards)
{
    $imageSize = 48;
    $numCols = 5;

    $numItems = count($userAwards);
    //var_dump( $userAwards );

    echo "<div id='siteawards' class='component' >";

    echo "<h3>Site Awards</h3>";

    echo "<div class='siteawards'>";

    echo "<table class='siteawards'><tbody>";

    global $developerCountBoundaries;
    global $developerPointBoundaries;

    for ($i = 0; $i < $numItems / 3; $i++) {
        // //    Alternating colours for table :)
        // if( $i%2==0 )
        // echo "<tr>";
        // else
        // echo "<tr class='alt'>";

        echo "<tr>";
        for ($j = 0; $j < $numCols; $j++) {
            $nOffs = ($i * $numCols) + $j;
            if ($nOffs >= $numItems) {
                continue;
            }

            $elem = $userAwards[$nOffs];

            //$awardedAt = $elem[ 'AwardedAt' ];
            $awardType = $elem['AwardType'];
            settype($awardType, 'integer');
            $awardData = $elem['AwardData'];
            $awardDataExtra = $elem['AwardDataExtra'];
            $awardGameTitle = $elem['Title'];
            $awardGameConsole = $elem['ConsoleName'];
            $awardGameImage = $elem['ImageIcon'];
            //$awardGameFlags = $elem[ 'Flags' ];
            $awardButGameIsIncomplete = (isset($elem['Incomplete']) && $elem['Incomplete'] == 1);
            $imgclass = 'badgeimg siteawards';

            if ($awardType == 1) {
                //echo $awardDataExtra;
                if ($awardDataExtra == '1') {
                    $tooltip = "MASTERED $awardGameTitle ($awardGameConsole)";
                    $imgclass = 'goldimage';
                } else {
                    $tooltip = "Completed $awardGameTitle ($awardGameConsole)";
                }

                if ($awardButGameIsIncomplete) {
                    $tooltip .= "...</br>but more achievements have been added!</br>Click here to find out what you're missing!";
                }

                $imagepath = $awardGameImage;
                $linkdest = "/Game/$awardData";
            } elseif ($awardType == 2) //    Developed a number of earned achievements
            {
                $tooltip = "Awarded for being a hard-working developer and producing achievements that have been earned over " . $developerCountBoundaries[$awardData] . " times!";

                $imagepath = getenv('APP_STATIC_URL') . "/Images/_Trophy" . $developerCountBoundaries[$awardData] . ".png";

                $linkdest = ''; //    TBD: referrals page?
            } elseif ($awardType == 3) //    Yielded an amount of points earned by players
            {
                $tooltip = "Awarded for producing many valuable achievements, providing over " . $developerPointBoundaries[$awardData] . " points to the community!";

                if ($awardData == 0) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00133.png";
                } elseif ($awardData == 1) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00134.png";
                } elseif ($awardData == 2) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00137.png";
                } elseif ($awardData == 3) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00135.png";
                } elseif ($awardData == 4) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00136.png";
                } else {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00136.png";
                }

                $linkdest = ''; //    TBD: referrals page?
            } elseif ($awardType == 4) //    Referrals
            {
                $tooltip = "Referred $awardData members";

                if ($awardData < 2) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00083.png";
                } elseif ($awardData < 3) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00083.png";
                } elseif ($awardData < 5) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00083.png";
                } elseif ($awardData < 10) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00083.png";
                } elseif ($awardData < 15) {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00083.png";
                } else {
                    $imagepath = getenv('APP_STATIC_URL') . "/Badge/00083.png";
                }

                $linkdest = ''; //    TBD: referrals page?
            } elseif ($awardType == 5) //    Signed up for facebook!
            {
                $tooltip = "Awarded for associating their account with Facebook! Thanks for spreading the word!";

                $imagepath = getenv('APP_STATIC_URL') . "/Images/_FBAssoc.png";
                $linkdest = "/controlpanel.php#facebook";
            } elseif ($awardType == 6)  //  Patreon Supporter
            {
                $tooltip = 'Awarded for being a Patreon supporter! Thank-you so much for your support!';

                $imagepath = getenv('APP_STATIC_URL') . '/Badge/PatreonBadge.png';
                $linkdest = 'https://www.patreon.com/retroachievements';
            } else {
                error_log("Unknown award type" . $awardType);
                continue;
            }

            $displayable = "<a href=\"$linkdest\"><img class=\"$imgclass\" alt=\"$tooltip\" title=\"$tooltip\" src=\"$imagepath\" width=\"$imageSize\" height=\"$imageSize\" /></a>";
            $tooltipImagePath = "$imagepath";
            $tooltipImageSize = 96; //64;    //    screw that, lets make it big!
            $tooltipTitle = "Site Award";

            $textWithTooltip = WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle,
                $tooltip);

            $newOverlayDiv = '';
            if ($awardButGameIsIncomplete) {
                $newOverlayDiv = WrapWithTooltip("<a href=\"$linkdest\"><div class=\"trophyimageincomplete\"></div></a>",
                    $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);
            }

            echo "<td><div class='trophycontainer'><div class='trophyimage'>$textWithTooltip</div>$newOverlayDiv</div></td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "</div>";

    //echo "<br/>";

    echo "</div>";
}

function RenderCompletedGamesList($user, $userCompletedGamesList)
{
    echo "<div id='completedgames' class='component' >";

    echo "<h3>Completion Progress</h3>";
    echo "<div id='usercompletedgamescomponent'>";

    echo "<table class='smalltable'><tbody>";
    echo "<tr><th colspan='2'>Game</th><th>Completion</th></tr>";

    $numItems = count($userCompletedGamesList);
    for ($i = 0; $i < $numItems; $i++) {
        $nextGameID = $userCompletedGamesList[$i]['GameID'];
        $nextConsoleName = $userCompletedGamesList[$i]['ConsoleName'];
        $nextTitle = $userCompletedGamesList[$i]['Title'];
        $nextImageIcon = $userCompletedGamesList[$i]['ImageIcon'];

        $nextMaxPossible = $userCompletedGamesList[$i]['MaxPossible'];

        $nextNumAwarded = $userCompletedGamesList[$i]['NumAwarded'];
        if ($nextNumAwarded == 0 || $nextMaxPossible == 0) //    Ignore 0 (div by 0 anyway)
        {
            continue;
        }

        $pctAwardedNormal = ($nextNumAwarded / $nextMaxPossible) * 100.0;

        $nextNumAwardedHC = isset($userCompletedGamesList[$i]['NumAwardedHC']) ? $userCompletedGamesList[$i]['NumAwardedHC'] : 0;
        $pctAwardedHC = ($nextNumAwardedHC / $nextMaxPossible) * 100.0;
        $pctAwardedHCProportional = ($nextNumAwardedHC / $nextNumAwarded) * 100.0; //    This is given as a proportion of normal completion!
        //$nextTotalAwarded = $nextNumAwarded + $nextNumAwardedHC;
        $nextTotalAwarded = $nextNumAwardedHC > $nextNumAwarded ? $nextNumAwardedHC : $nextNumAwarded; //    Just take largest

        if (!isset($nextMaxPossible)) {
            continue;
        }

        $nextPctAwarded = $userCompletedGamesList[$i]['PctWon'] * 100.0;
        //$nextCompletionPct = sprintf( "%2.2f", $nextNumAwarded / $nextMaxPossible );

        echo "<tr>";


        $tooltipImagePath = "$nextImageIcon";
        $tooltipImageSize = 96; //64;    //    screw that, lets make it big!
        $tooltipTitle = "$nextTitle";
        //$tooltipTitle = str_replace( "'", "\'", $tooltipTitle );
        $tooltip = "Progress: $nextNumAwarded achievements won out of a possible $nextMaxPossible";
        $tooltip = sprintf("%s (%01.1f%%)", $tooltip, ($nextTotalAwarded / $nextMaxPossible) * 100);

        $displayable = "<a href=\"/Game/$nextGameID\"><img alt=\"$tooltipTitle ($nextConsoleName)\" title=\"$tooltipTitle\" src=\"$nextImageIcon\" width=\"32\" height=\"32\" />";
        $textWithTooltip = WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);

        echo "<td class='gameimage'>$textWithTooltip</td>";
        $displayable = "<a href=\"/Game/$nextGameID\">$nextTitle</a>";
        $textWithTooltip = WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);
        echo "<td class=''>$textWithTooltip</td>";
        echo "<td class='progress'>";

        //if( $nextNumAwardedHC > 0 )
        {
            echo "<div class='progressbar completedgames'>";
            echo "<div class='completion' style='width:$pctAwardedNormal%'>";
            echo "<div class='completionhardcore' style='width:$pctAwardedHCProportional%' title='Hardcore earned: $nextNumAwardedHC/$nextMaxPossible'>";
            echo "&nbsp;";
            echo "</div>";
            echo "</div>";
            echo "$nextTotalAwarded/$nextMaxPossible won</br>";
            echo "</div>";
        }

        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";

    echo "</div>";
}

function RenderGameLeaderboardsComponent($gameID, $lbData)
{
    $numLBs = count($lbData);
    echo "<div class='component'>";
    echo "<h3>Leaderboards</h3>";

    if ($numLBs == 0) {
        echo "No leaderboards found: why not suggest some for this game? ";
        echo "<div class='rightalign'><a href='/leaderboardList.php'>Leaderboard List</a></div>";
    } else {
        echo "<table class='smalltable'><tbody>";

        $count = 0;
        foreach ($lbData as $lbItem) {
            $lbID = $lbItem['LeaderboardID'];
            $lbTitle = $lbItem['Title'];
            $lbDesc = $lbItem['Description'];
            $bestScoreUser = $lbItem['User'];
            $bestScore = $lbItem['Score'];
            $scoreFormat = $lbItem['Format'];

            //    Title
            echo "<tr class='alt'>";
            echo "<td colspan='2'>";
            echo "<div class='fixheightcellsmaller'><a href='/leaderboardinfo.php?i=$lbID'>$lbTitle</a></div>";
            echo "<div class='fixheightcellsmaller'>$lbDesc</div>";
            echo "</td>";
            echo "</tr>";

            //    Score/Best entry
            echo "<tr class='altdark'>";
            echo "<td>";
            //echo "<a href='/User/" . $bestScoreUser . "'><img alt='$bestScoreUser' title='$bestScoreUser' src='/UserPic/$bestScoreUser.png' width='32' height='32' /></a>";
            echo GetUserAndTooltipDiv($bestScoreUser, null, null, null, null, true);
            echo GetUserAndTooltipDiv($bestScoreUser, null, null, null, null, false);
            echo "</td>";
            echo "<td>";
            echo "<a href='/leaderboardinfo.php?i=$lbID'>";
            echo GetFormattedLeaderboardEntry($scoreFormat, $bestScore);
            echo "</a>";
            echo "</td>";

            echo "</tr>";

            $count++;
        }

        echo "</tbody></table>";
    }

    //echo "<div class='rightalign'><a href='/forumposthistory.php'>more...</a></div>";

    echo "</div>";
}

function RenderGameCompare($user, $gameID, $friendScores, $maxTotalPossibleForGame)
{
    echo "<div id='gamecompare' class='component' >";
    echo "<h3>Friends</h3>";
    if (isset($friendScores)) {
        echo "<div class='nicebox'>";
        echo "Compare to your friend:<br/>";
        echo "<table class='smalltable'><tbody>";
        foreach ($friendScores as $friendScoreName => $friendData) {
            $link = "/gamecompare.php?ID=$gameID&f=$friendScoreName";

            echo "<tr>";
            echo "<td>";
            echo GetUserAndTooltipDiv($friendScoreName, $friendData['RAPoints'], $friendData['Motto'],
                $friendData['RichPresenceMsg'], $friendData['LastUpdate'], true, $link);
            echo GetUserAndTooltipDiv($friendScoreName, $friendData['RAPoints'], $friendData['Motto'],
                $friendData['RichPresenceMsg'], $friendData['LastUpdate'], false, $link);
            echo "</td>";

            echo "<td>";
            echo "<a href='$link'>";
            echo $friendData['TotalPoints'] . "/$maxTotalPossibleForGame";
            echo "</a>";
            echo "</td>";

            echo "</tr>";
        }

        echo "</tbody></table>";

        echo "</br>";
        echo "Compare with any user:<br/>";

        echo "<form method='get' action='/gamecompare.php'>";
        echo "<input type='hidden' name='ID' value='$gameID'>";
        echo "<input size='24' name='f' type='text' class='searchboxgamecompareuser' />";
        echo "&nbsp;<input type='submit' value='Select' />";
        echo "</form>";

        echo "</div>";
    } else {
        echo "<div class='nicebox'>";
        if ($totalFriends > 0) {
            echo "None of your friends appear to have won any achievements for $gameTitle!<br/>";
            echo "Brag about your achievements to them <a href='/friends.php'>on their user wall</a>!";
        } else {
            echo "RetroAchievements is a lot more fun with friends!<br/><br/>";
            if ($user == null) {
                echo "<a href='/createaccount.php'>Create an account</a> or login and start earning achievements today!<br/>";
            } else {
                echo "Find friends to add <a href='/userList.php'>here</a>!<br/>";
                echo "<br/>";
                echo "or compare your progress in this game against any user:<br/>";

                echo "<form method='get' action='/gamecompare.php'>";
                echo "<input type='hidden' name='ID' value='$gameID'>";
                echo "<input size='24' name='f' type='text' class='searchboxgamecompareuser' />";
                echo "&nbsp;<input type='submit' value='Select' />";
                echo "</form>";
            }
        }
        echo "</div>";
    }
    echo "</div>";
}

function RenderRecentlyAwardedComponent($user, $points)
{
    $componentHeight = 514;
    $style = "height:$componentHeight" . "px";
    echo "<div class='component' style='$style'>";
    echo "<h3>recently awarded</h3>";

    $count = getRecentlyEarnedAchievements(10, null, $dataArray);

    $lastDate = '';

    echo "<table><tbody>";
    echo "<tr><th>Date</th><th>User</th><th>Achievement</th><th>Game</th></tr>";

    $iter = 0;

    for ($i = 0; $i < $count; $i++) {
        $timestamp = strtotime($dataArray[$i]['DateAwarded']);
        $dateAwarded = date("d M", $timestamp);

        if (date("d", $timestamp) == date("d")) {
            $dateAwarded = "Today";
        } elseif (date("d", $timestamp) == (date("d") - 1)) {
            $dateAwarded = "Y'day";
        }

        if ($lastDate !== $dateAwarded) {
            $lastDate = $dateAwarded;
        }

        //    Alternating colours for table :)
        if ($iter++ % 2 == 0) {
            echo "<tr>";
        } else {
            echo "<tr class='alt'>";
        }

        $wonAt = date("H:i", $timestamp);
        $nextUser = $dataArray[$i]['User'];
        $achID = $dataArray[$i]['AchievementID'];
        $achTitle = $dataArray[$i]['Title'];
        $achDesc = $dataArray[$i]['Description'];
        $achPoints = $dataArray[$i]['Points'];
        $badgeName = $dataArray[$i]['BadgeName'];
        //$badgeFullPath = getenv('APP_STATIC_URL')."/Badge/" . $badgeName . ".png";
        $gameTitle = $dataArray[$i]['GameTitle'];
        $gameID = $dataArray[$i]['GameID'];
        $gameIcon = $dataArray[$i]['GameIcon'];
        $consoleTitle = $dataArray[$i]['ConsoleTitle'];

        echo "<td>";
        echo "$dateAwarded $wonAt";
        echo "</td>";

        echo "<td>";
        //echo "<a href='/User/" . $nextUser . "'><img alt='$nextUser' title='$nextUser' src='/UserPic/$nextUser.png' width='32' height='32' /></a>";
        echo GetUserAndTooltipDiv($nextUser, null, null, null, null, true);
        echo "</td>";

        echo "<td><div class='fixheightcell'>";
        echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, true);
        echo "</div></td>";

        echo "<td><div class='fixheightcell'>";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleTitle, true);
        echo "</div></td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "<br/>";
    echo "</div>";
}

function RenderRecentlyUploadedComponent($numToFetch)
{
    echo "<div class='component'>";
    echo "<h3>New Achievements</h3>";

    $numFetched = getLatestNewAchievements($numToFetch, $dataOut);
    if ($numFetched > 0) {
        echo "<table class='sidebar'><tbody>";
        echo "<tr><th>Added</th><th>Achievement</th><th>Game</th></tr>";

        $lastDate = '';
        $iter = 0;

        for ($i = 0; $i < $numToFetch; $i++) {
            $nextData = $dataOut[$i];

            $timestamp = strtotime($nextData['DateCreated']);
            $dateAwarded = date("d M", $timestamp);

            if (date("d", $timestamp) == date("d")) {
                $dateAwarded = "Today";
            } elseif (date("d", $timestamp) == (date("d") - 1)) {
                $dateAwarded = "Y'day";
            }

            if ($lastDate !== $dateAwarded) {
                $lastDate = $dateAwarded;
            }
            //    Alternating colours for table :)
            if ($iter++ % 2 == 0) {
                echo "<tr>";
            } else {
                echo "<tr class='alt'>";
            }

            $uploadedAt = date("H:i", $timestamp);
            $achID = $nextData['ID'];
            $achTitle = $nextData['Title'];
            $achDesc = $nextData['Description'];
            $achPoints = $nextData['Points'];
            $gameID = $nextData['GameID'];
            $gameTitle = $nextData['GameTitle'];
            $gameIcon = $nextData['GameIcon'];
            $achBadgeName = $nextData['BadgeName'];
            $consoleName = $nextData['ConsoleName'];
            //$badgeFullPath = getenv('APP_STATIC_URL')."/Badge/" . $achBadgeName . ".png";

            echo "<td>$dateAwarded $uploadedAt</td>";
            echo "<td style='width:50%'><div class='fixheightcell'>";
            //echo "<img title='$achTitle' alt='$achTitle' src='$badgeFullPath' width='32' height='32' />";
            echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, true);
            echo "</div></td>";
            echo "<td><div class='fixheightcell'>";
            echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, true);
            echo "</div></td>";

            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "<br/>";

        echo "<div class='morebutton'><a href='/achievementList.php?s=17'>more...</a></div>";

        echo "</div>";
    }
}

function RenderDeveloperStats($user, $type)
{
    echo "<div class='component'>";
    echo "<h3>Developer Stats</h3>";

    $devData = GetDeveloperStats(99, $type);
    if (count($devData) > 0) {
        $tableType = ($type == 2) ? "Num Achievements Won By Others" : (($type == 1) ? "Num Points Allocated" : "Num Achievements Developed");

        echo "<table><tbody>";
        echo "<tr><th>Rank</th><th>Developer</th><th>$tableType</th></tr>";

        for ($i = 0; $i < count($devData); $i++) {
            $nextData = $devData[$i];

            $rank = $i + 1;
            $developer = $nextData['Author'];
            $numAchievements = $nextData['NumCreated'];

            echo "<tr>";
            echo "<td>$rank</td>";

            echo "<td><div class='fixheightcell'>";
            echo GetUserAndTooltipDiv($developer, null, null, null, null, true);
            echo GetUserAndTooltipDiv($developer, null, null, null, null, false);
            echo "</div></td>";

            echo "<td>$numAchievements</td>";

            echo "</tr>";
        }
        echo "</tbody></table>";

        echo "</div>";
    }
}

function RenderDocsComponent()
{
    echo "
      <div class='component' style='text-align: center'>
        <!--h3>Documentation</h3-->
        <div id='docsbox' class='infobox'>
          <div>
            Read the <a href='https://docs.retroachievements.org/' target='_blank' rel='noopener'>Documentation</a> & <a href='https://docs.retroachievements.org/FAQ/' target='_blank' rel='noopener'>FAQ</a>.
          </div>
        </div>
      </div>";
}

function RenderCurrentlyOnlineComponent($user)
{
    if (isset($user)) {
        //    not impl
    } else {
        //    global

        echo "<div class='component'>";
        echo "<h3>Currently Online</h3>";
        echo "<div id='playersonlinebox' class='infobox'>";

        $playersArray = getCurrentlyOnlinePlayers();

        $numPlayers = count($playersArray);
        echo "<div>There are currently <strong>$numPlayers</strong> players online.</div>";

        //$numOutput = 0;
        //foreach( $playersArray as $nextPlayer )
        //{
        //    if( $numOutput > 0 && $numOutput == $numPlayers - 1 )
        //    {
        //        echo " and ";
        //    }
        //    elseif( $numOutput > 0 )
        //    {
        //        echo ", ";
        //    }
        //    echo GetUserAndTooltipDiv( $nextPlayer[ 'User' ], $nextPlayer[ 'RAPoints' ], NULL, $nextPlayer[ 'LastActivity' ], $nextPlayer[ 'LastActivityAt' ], FALSE );
        //    $numOutput++;
        //}

        echo "</div>";

        echo "<div class='rightfloat lastupdatedtext'><small><span id='playersonline-update'></span></small></div>";
        echo "</div>";
    }
}

function RenderActivePlayersComponent()
{
    echo "<div class='component activeplayerscomponent' >";
    echo "<h3>Active Players</h3>";

    echo "<div id='activeplayersbox' style='min-height: 54px'>";
    //    fetch via ajaphp
    $playersArray = getCurrentlyOnlinePlayers();
    $numPlayers = count($playersArray);
    echo "There are currently <strong>$numPlayers</strong> players online.<br/>";
    //$numOutput = 0;
    //foreach( $playersArray as $nextPlayer )
    //{
    //    if( $numOutput > 0 && $numOutput == $numPlayers - 1 )
    //    {
    //        echo " and ";
    //    }
    //    elseif( $numOutput > 0 )
    //    {
    //        echo ", ";
    //    }
    //
    //    echo GetUserAndTooltipDiv( $nextPlayer[ 'User' ], $nextPlayer[ 'RAPoints' ], NULL, $nextPlayer[ 'LastActivity' ], $nextPlayer[ 'LastActivityAt' ], FALSE );
    //    $numOutput++;
    //}
    echo "</div>";

    echo "<div class='rightfloat lastupdatedtext'><small><span id='activeplayers-update'></span></small></div>";
    echo "</div>";
}

function RenderAOTWComponent($achID, $forumTopicID)
{
    if (!getAchievementMetadata($achID, $achData)) {
        return;
    }

    echo "<div class='component aotwcomponent' >";
    echo "<h3>Achievement of the Week</h3>";
    echo "<div id='aotwbox' style='text-align:center;'>";

    $gameID = $achData['GameID'];
    $gameTitle = $achData['GameTitle'];
    $gameIcon = $achData['GameIcon'];
    $consoleName = $achData['ConsoleName'];

    $achID = $achData['AchievementID'];
    $achTitle = $achData['AchievementTitle'];
    $achDesc = $achData['Description'];
    $achBadgeName = $achData['BadgeName'];
    $achPoints = $achData['Points'];
    $achTruePoints = $achData['TrueRatio'];

    echo "Achievement: ";
    echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, true);
    echo "<br/>";

    echo "on Game: ";
    echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 32);
    echo "<br/>";

    echo "<span class='clickablebutton'><a href='/viewtopic.php?t=$forumTopicID'>Join this tournament!</a></span>";

    echo "</div>";

    echo "</div>";
}

function RenderRecentForumPostsComponent($numToFetch = 4)
{
    echo "<div class='component' >";
    echo "<h3>Forum Activity</h3>";

    if (getRecentForumPosts(0, $numToFetch, 45, $recentPostData) != 0) {
        echo "<table class='recentforumposts'><tbody>";
        echo "<tr><th>At</th><th>User</th><th>Message</th><th>Topic</th></tr>";

        $lastDate = '';
        $iter = 0;

        for ($i = 0; $i < $numToFetch; $i++) {
            $nextData = $recentPostData[$i];
            $timestamp = strtotime($nextData['PostedAt']);
            $datePosted = date("d M", $timestamp);

            if (date("d", $timestamp) == date("d")) {
                $datePosted = "Today";
            } elseif (date("d", $timestamp) == (date("d") - 1)) {
                $datePosted = "Y'day";
            }

            $postedAt = date("H:i", $timestamp);

            if ($lastDate !== $datePosted) {
                $lastDate = $datePosted;
            }

            echo "<tr>";

            $shortMsg = $nextData['ShortMsg'] . "...";
            $author = $nextData['Author'];
            $userPoints = $nextData['RAPoints'];
            $userMotto = $nextData['Motto'];
            $commentID = $nextData['CommentID'];
            $forumTopicID = $nextData['ForumTopicID'];
            $forumTopicTitle = $nextData['ForumTopicTitle'];

            echo "<td>$datePosted $postedAt</td>";

            echo "<td>";
            echo GetUserAndTooltipDiv($author, $userPoints, $userMotto, null, null, true);
            echo "</td>";

            echo "<td class='recentforummsg'>$shortMsg<a href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID'>[view]</a></td>";

            echo "<td><div class='fixheightcelllarger recentforumname'>";
            echo "<a href='/viewtopic.php?t=$forumTopicID&amp;c='>$forumTopicTitle</a>";
            echo "</div></td>";

            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        error_log(__FUNCTION__);
        error_log("Cannot get latest forum posts!");
    }

    echo "<span class='morebutton'><a href='/forumposthistory.php'>more...</a></span>";

    echo "</div>";
}

function RenderStaticDataComponent($staticData)
{
    echo "<div class='component statistics'>";
    echo "<h3>Statistics</h3>";

    $numGames = $staticData['NumGames'];
    $numAchievements = $staticData['NumAchievements'];
    $numAwarded = $staticData['NumAwarded'];
    $numRegisteredPlayers = $staticData['NumRegisteredUsers'];

    $avAwardedPerPlayer = 0;
    if ($numRegisteredPlayers > 0) {
        $avAwardedPerPlayer = sprintf("%1.2f", ($numAwarded / $numRegisteredPlayers));
    }

    $lastRegisteredUser = $staticData['LastRegisteredUser'];
    $lastRegisteredUserAt = $staticData['LastRegisteredUserAt'];
    $lastAchievementEarnedID = $staticData['LastAchievementEarnedID'];
    $lastAchievementEarnedTitle = $staticData['LastAchievementEarnedTitle'];
    $lastAchievementEarnedByUser = $staticData['LastAchievementEarnedByUser'];
    $lastAchievementEarnedAt = $staticData['LastAchievementEarnedAt'];
    $totalPointsEarned = $staticData['TotalPointsEarned'];

    $nextGameToScanID = $staticData['NextGameToScan'];
    $nextGameToScan = $staticData['NextGameTitleToScan'];
    $nextGameToScanIcon = $staticData['NextGameToScanIcon'];
    $nextGameConsoleToScan = $staticData['NextGameToScanConsole'];

    $nextUserToScan = $staticData['NextUserToScan'];

    $niceRegisteredAt = date("d M\nH:i", strtotime($lastRegisteredUserAt));

    if ($lastRegisteredUser == null) {
        $lastRegisteredUser = "unknown";
    }

    if ($lastRegisteredUserAt == null) {
        $lastRegisteredUserAt = "unknown";
    }

    echo "<div class='infobox'>";
    echo "There are ";
    echo "<a title='Achievement List' href='/gameList.php?s=2'>$numAchievements</a>";
    echo " achievements registered for ";
    echo "<a title='Game List' href='/gameList.php?s=1'>$numGames</a> games. ";

    echo "<a title='Achievement List' href='/achievementList.php'>$numAwarded</a>";
    echo " achievements have been awarded to the ";
    echo "<a title='User List' href='/userList.php'>$numRegisteredPlayers</a>";
    echo " registered players (average: $avAwardedPerPlayer per player)<br/>";

    echo "<br/>";

    echo "Since 2nd March 2013, a total of ";
    echo "<span title='Awesome!'><strong>$totalPointsEarned</strong></span>";
    echo " points have been earned by users on RetroAchievements.org.<br/>";

    echo "<br/>";

    echo "The last registered user was ";
    echo GetUserAndTooltipDiv($lastRegisteredUser, null, null, null, null, false);
    //echo "<a href='/User/$lastRegisteredUser'>$lastRegisteredUser</a>";
    echo " on $niceRegisteredAt.<br/>";

    //echo "<br/>";
    //echo "Next game to scan: ";
    //echo GetGameAndTooltipDiv( $nextGameToScanID, $nextGameToScan, $nextGameToScanIcon, $nextGameConsoleToScan, FALSE, 32, TRUE );
    //echo "<br/>";
    //echo "Next user to scan: ";
    //echo GetUserAndTooltipDiv( $nextUserToScan, NULL, NULL, NULL, NULL, FALSE );
    //echo "The last achievement earned was ";
    //echo "<a href='/Achievement/$lastAchievementEarnedID'>$lastAchievementEarnedTitle</a>";
    //echo " by ";
    //echo "<a href='/User/$lastAchievementEarnedByUser'>$lastAchievementEarnedByUser</a><br/>";

    echo "</div>";

    //var_dump( $staticData );

    echo "</div>";
}

function RenderTwitchTVStream($vidWidth = 300, $vidHeight = 260, $componentPos = '', $overloadVideoID = 0)
{
    echo "<div class='component $componentPos stream' >";
    echo "<h3>Twitch Stream</h3>";

    $archiveURLs = array();
    if ($componentPos == 'left') {
        $query = "SELECT *
            FROM PlaylistVideo
            ORDER BY Added DESC";

        $dbResult = s_mysql_query($query);

        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $archiveURLs[$nextData['ID']] = $nextData;
        }
    }

    //$chatWidth = 300;
    //$chatHeight = 335;

    if ($overloadVideoID !== 0 && isset($archiveURLs[$overloadVideoID])) {
        $vidTitle = htmlspecialchars($archiveURLs[$overloadVideoID]['Title']);
        $vidURL = $archiveURLs[$overloadVideoID]['Link'];
        $vidChapter = substr($vidURL, strrpos($vidURL, "/") + 1);

        //<object type="application/x-shockwave-flash" height="378" width="620" id="live_embed_player_flash" data="http://www.twitch.tv/widgets/live_embed_player.swf?channel=retroachievementsorg" bgcolor="#000000"><param name="allowFullScreen" value="true" /><param name="allowScriptAccess" value="always" /><param name="allowNetworking" value="all" /><param name="movie" value="http://www.twitch.tv/widgets/live_embed_player.swf" /><param name="flashvars" value="hostname=www.twitch.tv&channel=retroachievementsorg&auto_play=true&start_volume=25" /></object><a href="http://www.twitch.tv/retroachievementsorg" style="padding:2px 0px 4px; display:block; width:345px; font-weight:normal; font-size:10px;text-decoration:underline; text-align:center;">Watch live video from RetroAchievementsOrg on www.twitch.tv</a>
        $videoHTML = "<object type='application/x-shockwave-flash' height='$vidHeight' width='$vidWidth' id='clip_embed_player_flash' data='//www.twitch.tv/widgets/archive_embed_player.swf'>
            <param name='movie' value='//www.twitch.tv/widgets/archive_embed_player.swf'>
            <param name='allowScriptAccess' value='always'>
            <param name='allowNetworking' value='all'>
            <param name='allowFullScreen' value='true'>
            <param name='flashvars' value='title=$vidTitle&amp;channel=" . getenv('TWITCH_CHANNEL') . "&amp;auto_play=$autoplay&amp;start_volume=25&amp;chapter_id=$vidChapter'>
            </object>";

        //$videoHTML = '<iframe src="http://player.twitch.tv/?'.getenv('TWITCH_CHANNEL').'&muted=true" height="378" width="620" frameborder="0" scrolling="no" allowfullscreen="true"></iframe>';
    } else {
        $muted = 'false';
        if (isAtHome()) {
            $muted = 'true';
        }

        $videoHTML = '<iframe src="//player.twitch.tv/?channel=' . getenv('TWITCH_CHANNEL') . '&muted=$muted" height="168" width="300" frameborder="0" scrolling="no" allowfullscreen="true"></iframe>';

        //$videoHTML = "<object type='application/x-shockwave-flash' height='$vidHeight' width='$vidWidth' id='live_embed_player_flash' data='http://www.twitch.tv/widgets/live_embed_player.swf?channel=".getenv('TWITCH_CHANNEL')."'>
        //    <param name='allowFullScreen' value='true' />
        //    <param name='allowScriptAccess' value='always' />
        //    <param name='allowNetworking' value='all' />
        //    <param name='movie' value='http://www.twitch.tv/widgets/live_embed_player.swf' />
        //    <param name='flashvars' value='hostname=www.twitch.tv&amp;channel=".getenv('TWITCH_CHANNEL')."&amp;auto_play=$autoplay&amp;start_volume=25' />
        //    </object>";
    }

    echo "<div class='streamvid'>";
    echo $videoHTML;
    echo "</div>";

    //echo "<div class='streamchat'>";
    //echo "<iframe frameborder='0' scrolling='no' id='chat_embed' src='http://twitch.tv/chat/embed?channel=".getenv('TWITCH_CHANNEL')."&amp;popout_chat=true' height='$chatHeight' width='$chatWidth'></iframe>";
    //echo "</div>";

    echo "<span class='clickablebutton'><a href='//www.twitch.tv/" . getenv('TWITCH_CHANNEL') . "' class='trk'>see us on twitch.tv</a></span><span class='morebutton'><a style='float:right' href='/largechat.php'>RA Cinema</a></span>";

    if ($componentPos == 'left') {
        echo "<form method='post' style='text-align:right; padding:4px 0px'>";
        echo "Currently Watching:&nbsp;";
        echo "<select name='g' onchange=\"reloadTwitchContainer( this.value, 600, 500 ); return false;\">";
        $selected = ($overloadVideoID == 0) ? 'selected' : '';
        echo "<option value='0' $selected>--Live--</option>";
        foreach ($archiveURLs as $dataElementID => $dataElementObject) {
            $vidTime = $dataElementObject['Added'];
            $niceDate = getNiceDate(strtotime($vidTime));
            $vidAuthor = $dataElementObject['Author'];
            $vidTitle = $dataElementObject['Title'];
            $vidID = $dataElementObject['ID'];
            $name = "$vidTitle ($vidAuthor, $niceDate)";
            $selected = ($overloadVideoID == $vidID) ? 'selected' : '';
            echo "<option value='$dataElementID' $selected>$name</option>";
        }
        echo "</select>";
        echo "</form>";
    }

    echo "</div>";
}

function RenderChat($user, $chatHeight = 380, $chatboxat = '', $addLinkToPopOut = false)
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        return '';
    }

    $location = "component $chatboxat";

    $isLargeChat = ($chatboxat == 'left');

    $cssLargeSuffix = $isLargeChat ? "large" : "";
    $chatInputSize = $isLargeChat ? '95' : '39';

    echo "<div class='$location stream'>";
    echo "<h3>Chat</h3>";

    echo "<div id='chatcontainer$cssLargeSuffix' style='height:$chatHeight" . "px'>";
    echo "<div class='chatinnercontainer$cssLargeSuffix'>";
    echo "<table id='chatbox'><tbody>";

    $userPicSize = 24;

    echo "<tr id='chatloadingfirstrow'>";
    echo "<td class='chatcell'><img src='" . getenv('APP_STATIC_URL') . "/Images/loading.gif' width='16' height='16' alt='loading icon'/></td>";
    //echo "<td class='chatcell'><img src='" . getenv('APP_STATIC_URL') . "/Images/tick.gif' width='16' height='16' alt='loading icon'/></td>";
    echo "<td class='chatcell'></td>";
    echo "<td class='chatcellmessage'>Loading chat...</td>";
    echo "</tr>";
    echo "</tbody></table>";

    echo "</div>";
    echo "</div>";

    //echo "<div class='rightalign smallpadding'>";

    echo "<table><tbody>";
    echo "<tr>";

    if (isset($user)) {
        echo "<td class='chatcell'><a href='/User/$user'><img src='/UserPic/$user" . ".png' width='32' height='32'/></a></td>";

        echo "<td class='chatcell'>";
        echo "<div class='rightalign'>";
        echo "<input type='text' id='chatinput' maxlength='2000' size=$chatInputSize onkeydown='handleKey(event)'/>";
        echo "&nbsp;<input type='button' value='Send' onclick='sendMessage();' />";
        echo "</div>";
        echo "</td>";
    } else {
        echo "<td class='chatcell'><img src='/UserPic/_User.png' width='32' height='32' alt='default user pic'/></td>";

        echo "<td class='chatcell'>";
        echo "<div class='rightalign'>";
        echo "<input disabled readonly type='text' id='chatinput' maxlength='2000' size='39'/>";
        echo "&nbsp;<input disabled type='button' value='Send' onclick='sendMessage();' />";
        echo "</div>";
        echo "</td>";
    }

    echo "</tr>";
    echo "</tbody></table>";

    echo "<div id='sound'></div>";

    echo "<div class='rightalign'>Mute&nbsp;<input id='mutechat' type='checkbox' value='Mute' />";


    if ($addLinkToPopOut) {
        echo "&nbsp;<a href='#' onclick=\"window.open('" . getenv('APP_URL') . "/popoutchat.php', 'chat', 'status=no,height=560,width=340'); return false;\">Pop-out Chat</a>";
    }
    echo "</div>";


    //echo "<div id='tlkio' data-channel='retroachievements' data-theme='/css/chat.css' style='width:100%;height:400px;'></div><script async src='http://tlk.io/embed.js' type='text/javascript'></script>";
    echo "</div>";
}

function RenderBoxArt($imagePath)
{
    echo "<div class='component gamescreenshots'>";
    echo "<h3>Box Art</h3>";
    echo "<table><tbody>";
    echo "<tr><td>";
    echo "<img src='$imagePath' style='max-width:100%;' />";
    echo "</td></tr>";
    echo "</tbody></table>";
    echo "</div>";
}

function RenderGameAlts($gameAlts)
{
    echo "<div class='component gamealts'>";
    echo "<h3>Similar Games</h3>";
    echo "Have you tried:</br>";
    echo "<table><tbody>";
    foreach ($gameAlts as $nextGame) {
        echo "<tr>";
        $gameID = $nextGame['gameIDAlt'];
        $gameTitle = $nextGame['Title'];
        $gameIcon = $nextGame['ImageIcon'];
        $consoleName = $nextGame['ConsoleName'];
        $points = $nextGame['Points'];
        $totalTP = $nextGame['TotalTruePoints'];
        settype($points, 'integer');
        settype($totalTP, 'integer');

        echo "<td>";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, true);
        echo "</td>";

        echo "<td>";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 32, true);
        echo "</td>";

        echo "<td>";
        echo "$points points<span class='TrueRatio'> ($totalTP)</span>";
        echo "</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
}

function RenderTwitterFeed()
{
    echo "<div class='component stream'>";
    echo "<h3>Twitter Feed</h3>";

    echo "<a class='twitter-timeline'  href='https://twitter.com/RetroCheevos'  data-widget-id='365153450822103040'>Tweets by @RetroCheevos</a>";
    echo "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+\"://platform.twitter.com/widgets.js\";fjs.parentNode.insertBefore(js,fjs);}}(document,\"script\",\"twitter-wjs\");</script>";

    echo "</div>";
}

function RenderTutorialComponent()
{
    echo "<div class='component tutorial' >";
    echo "<h3>How Do I Play?</h3>";
    echo "<p><a href='/'>RetroAchievements</a> provides emulators for your PC where you can earn achievements while you play games!</p>";
    echo "<p><i>\"...like Xbox Live&trade; for emulation!\"</i></p>";
    echo "<p><a href='/download.php'>Download an emulator</a> for your chosen console, <a href='//www.retrode.com/'>find</a> some <a href='//www.lmgtfy.com/?q=download+mega+drive+roms'>ROMs</a> and join the fun!</p>";

    echo "</div>";
}

function RenderLinkToGameForum($user, $cookie, $gameTitle, $gameID, $forumTopicID, $permissions = 0)
{
    if (isset($forumTopicID) && $forumTopicID != 0 && getTopicDetails($forumTopicID, $topicData)) {
        echo "<a href='/viewtopic.php?t=$forumTopicID'>View official forum topic for $gameTitle here</a>";
    } else {
        echo "No forum topic";
        if (isset($user) && $permissions >= 3) // 3 == Developer
        {
            echo " - <a href='/generategameforumtopic.php?u=$user&c=$cookie&g=$gameID'>Create the official forum topic for $gameTitle</a>";
        }
    }
}

function RenderDocType($isOpenGraphPage = false)
{
    echo "<!doctype html>";
    //echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML+RDFa 1.0//EN' 'http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd'>\n";
    echo "<html xmlns='https://www.w3.org/1999/xhtml' lang='en' xml:lang='en' ";

    if ($isOpenGraphPage) {
        echo "prefix=\"og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#\" ";
    }

    echo ">\n";
}

function RenderSharedHeader($user)
{
    echo "<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css' />\n";
    echo "<link href='https://fonts.googleapis.com/css?family=Rosario' rel='stylesheet' type='text/css' />\n";
    echo "<link rel='stylesheet' href='" . CSS_FILE . "' type='text/css' media='screen' />\n";
    $customCSS = RA_ReadCookie('RAPrefs_CSS');
    if ($customCSS !== false && strlen($customCSS) > 2) {
        echo "<link rel='stylesheet' href='$customCSS' type='text/css' media='screen' />\n";
    }

    echo "<link rel='icon' type='image/png' href='/favicon.png' />\n";
    echo "<link rel='image_src' href='/Images/RA_Logo10.png' />\n";
    echo "<meta http-equiv='content-type' content='text/html; charset=UTF-8' />\n";
    echo "<meta name='robots' content='all' />\n";
    //echo "<meta name='Copyright' content='Copyright 2014' />\n";
    echo "<meta name='description' content='Adding achievements to your favourite retro games since 2012' />\n";
    echo "<meta name='keywords' content='games, retro, computer games, mega drive, genesis, rom, emulator, achievements' />\n";
    echo '<meta property="fb:app_id" content="490904194261313" />';
    echo "<meta name='viewport' content='width=device-width,user-scalable = no'/>\n";

    echo '<meta name="theme-color" content="#2C2E30">';
    // echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    // echo '<meta name="apple-mobile-web-app-status-bar-style" content="black">';
    // echo '<meta name="apple-mobile-web-app-status-bar-style" content="black">';
    echo '<meta name="msapplication-TileColor" content="#2C2E30">';
    echo '<meta name="msapplication-TileImage" content="/favicon.png">';
    echo '<link rel="shortcut icon" type="image/png" href="/favicon.png" sizes="16x16 32x32 64x64">';
    echo '<link rel="apple-touch-icon" sizes="120x120" href="/favicon.png">';

    echo "<link rel='stylesheet' href='https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/themes/sunny/jquery-ui.css' type='text/css' />\n";
    echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.js' type='text/javascript'></script>\n";
    echo "<script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.js' type='text/javascript'></script>\n";

    echo "
    <script type='text/javascript'>
        window.fbAsyncInit = function() {
          FB.init({
            appId      : 490904194261313, // App ID
            channelUrl : '" . getenv('APP_URL') . "/channel.php', // Channel File
            status     : true, // check login status
            cookie     : true, // enable cookies to allow the server to access the session
            xfbml      : true  // parse XFBML
          });
        };
        // Load the SDK Asynchronously
        (function(d){
           var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
           if (d.getElementById(id)) {return;}
           js = d.createElement('script'); js.id = id; js.async = true;
           js.src = \"//connect.facebook.net/en_US/all.js\";
           ref.parentNode.insertBefore(js, ref);
         }(document));
    </script>\n";

    //    jQuery, and custom js
    //echo "<script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js'></script>\n";
    //echo "<script type='text/javascript' src='/js/jquery-ui-1.10.2.custom.min.js'></script>\n";
    echo "<script type='text/javascript' src='/js/all.js'></script>\n";

    global $mobileBrowser;
    if ($mobileBrowser) {
        echo "<link rel='stylesheet' type='text/css' href='/css/_mobile.css'>";
    }
}

function RenderFBMetadata($title, $OGType, $imageURL, $thisURL, $description)
{
    echo "<meta property='og:type' content='retroachievements:$OGType' />\n";
    echo "<meta property='og:image' content='" . getenv('APP_STATIC_URL') . "$imageURL' />\n";
    echo "<meta property='og:url' content='" . getenv('APP_URL') . "$thisURL' />\n";
    echo "<meta property='og:title' content=\"$title\" />\n";
    echo "<meta property='og:description' content=\"$description\" />\n";
}

function RenderTitleTag($title, $user)
{
    echo "<title>";

    if ($title !== null) {
        echo "$title - ";
    }

    echo getenv('APP_NAME');
    //"RetroAchievements.org";

    echo "</title>";

    //<!-- YAY XMAS! -->
    //echo "<script src='js/snowstorm.js'></script>
    //<script>
    //$( function() {
    //    //    Onload:
    //    $('body').append( \"<img src='https://i.retroachievements.org/Images/003754.png' width='280' height='280' style='position:fixed;left:0px;top:0px;width:100%;height:100%;z-index:-50;'>\" );
    //});
    //</script>";
}

function RenderGoogleTracking()
{
    echo " <script type=\"text/javascript\">

    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-37462159-1']);
    _gaq.push(['_trackPageview']);

    (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();

    </script>";
}

//    PHPBB-Style
//    17:05 18/04/2013
function cb_injectAchievementPHPBB($matches)
{
    if (count($matches) > 1) {
        getAchievementMetadata($matches[2], $achData);
        $achID = $achData['AchievementID'];
        $achName = $achData['AchievementTitle'];
        $achDesc = $achData['Description'];
        $achPoints = $achData['Points'];
        $gameName = $achData['GameTitle'];
        $badgeName = $achData['BadgeName'];
        $consoleName = $achData['ConsoleName'];

        return GetAchievementAndTooltipDiv($achID, $achName, $achDesc, $achPoints, $gameName, $badgeName, $consoleName,
            false);
    }
    return "";
}

//    17:05 18/04/2013
function cb_injectUserPHPBB($matches)
{
    if (count($matches) > 1) {
        $user = $matches[2];
        return GetUserAndTooltipDiv($user, null, null, null, null, false);
    }
    return "";
}

//    17:05 18/04/2013
function cb_injectGamePHPBB($matches)
{
    if (count($matches) > 1) {
        $gameID = $matches[2];
        getGameTitleFromID($gameID, $gameName, $consoleIDOut, $consoleName, $forumTopicID, $gameData);

        return GetGameAndTooltipDiv($gameID, $gameName, $gameData['GameIcon'], $consoleName);
    }
    return "";
}

/**
 * from http://stackoverflow.com/questions/5830387/how-to-find-all-youtube-video-ids-in-a-string-using-a-regex
 */
function linkifyYouTubeURLs($text)
{
    // http://www.youtube.com/v/YbKzgRwF91w
    // http://www.youtube.com/watch?v=1zMHaHPXqqg
    // http://youtu.be/-D06lkNS3-k
    // https://youtu.be/66ohBw9O6NU
    // https://www.youtube.com/embed/Fmwr6T2JHc4
    // https://www.youtube.com/watch?v=1YiNYWpwn7o
    // www.youtube.com/watch?v=Yjba9rvs4iU

    $pattern = '~
        # Match non-linked youtube URL in the wild. (Rev:20130823)
        (?:https?://)?    # Optional scheme. Either http or https.
        (?:[0-9A-Z-]+\.)? # Optional subdomain.
        (?:               # Group host alternatives.
          youtu\.be/      # Either youtu.be,
        | youtube\.com    # or youtube.com followed by
          \S*             # Allow anything up to VIDEO_ID,
          [^\w\-\s]       # but char before ID is non-ID char.
        )                 # End host alternatives.
        ([\w\-]{11})      # $1: VIDEO_ID is exactly 11 chars.
        (?=[^\w\-]|$)     # Assert next char is non-ID or EOS.
        (?!               # Assert URL is not pre-linked.
          [?=&+%\w.-]*    # Allow URL (query) remainder.
          (?:             # Group pre-linked alternatives.
            [\'"][^<>]*>  # Either inside a start tag,
          | </a>          # or inside <a> element text contents.
          )               # End recognized pre-linked alts.
        )                 # End negative lookahead assertion.
        ([?=&+%\w.-]*)        # Consume any URL (query) remainder.
        ~ix';

    $text = preg_replace(
        $pattern,
        '<div class="embed-responsive embed-responsive-16by9 mb-3"><iframe class="embed-responsive-item" src="//www.youtube-nocookie.com/embed/$1" allowfullscreen></iframe></div>',
        $text);
    return $text;
}

function linkifyTwitchURLs($text)
{
    // https://www.twitch.tv/videos/270709956
    // https://www.twitch.tv/collections/cWHCMbAY1xQVDA
    // https://www.twitch.tv/gamingwithmist/v/40482810
    // https://clips.twitch.tv/AmorphousCautiousLegPanicVis

    if (strpos($text, "twitch.tv") !== false) {
        $vidChapter = substr($text, strrpos($text, "/") + 1);
        $iframeUrl = '//player.twitch.tv/?video=' . $vidChapter;
        if (strpos($text, "twitch.tv/collections") !== false) {
            $iframeUrl = '//player.twitch.tv/?collection=' . $vidChapter;
        }
        if (strpos($text, "clips.twitch.tv") !== false) {
            $iframeUrl = '//clips.twitch.tv/embed?clip=' . $vidChapter;
        }
        $iframeUrl .= '&autoplay=false';
        $text = '<div class="embed-responsive embed-responsive-16by9 mb-3"><iframe class="embed-responsive-item" src="' . $iframeUrl . '" allowfullscreen></iframe></div>';
    }

    return $text;
}

/**
 * see https://regex101.com/r/mQamDF/1
 */
function linkifyImgurURLs($text)
{
    // https://imgur.com/gallery/bciLIYm.gifv
    // https://imgur.com/a/bciLIYm.gifv
    // https://i.imgur.com/bciLIYm.gifv
    // https://i.imgur.com/bciLIYm.webm
    // https://i.imgur.com/bciLIYm.mp4

    // https://imgur.com/gallery/bciLIYm -> no extension -> will be ignored (turns out as link)
    // https://imgur.com/a/bciLIYm.gif -> replaced by gifv - potentially broken if it's a static image
    // https://imgur.com/a/bciLIYm.jpg -> downloads as gif if original is a gif, potentially large :/ can't do much about that

    $pattern = '~(?:https?://)?(?:[0-9a-z-]+\.)?imgur\.com(?:[\w/]*/)?(\w+)(\.\w+)?~ix';
    // $text = 'https://i.imgur.com/bciLIYm https://i.imgur.com/bciLIYm.mp4 https://imgur.com/a/bciLIYm.gif';
    preg_match_all($pattern, $text, $matches);
    if (!count($matches[0])) {
        return $text;
    }
    $replacements = [];
    for ($i = 0; $i < count($matches[0]); $i++) {
        $id = $matches[1][$i];
        $extension = $matches[2][$i] ?? null;
        $extension = $extension === '.gif' ? '.gifv' : $extension;
        $replacements[$i] = $matches[0][$i];
        if (in_array($extension, ['.gifv', '.mp4', '.webm'])) {
            $replacements[$i] = '<a href="//imgur.com/' . $id . '" target="_blank" rel="noopener"><div class="embed-responsive embed-responsive-16by9"><video controls class="embed-responsive-item"><source src="//i.imgur.com/' . $id . '.mp4" type="video/mp4"></video></div><div class="text-right mb-3"><small>view on imgur</small></div></a>';
        } elseif (in_array($extension, ['.jpg', '.png', '.jpeg'])) {
            $replacements[$i] = '<a href="//imgur.com/' . $id . '" target="_blank" rel="noopener"><img class="img-fluid" src="//i.imgur.com/' . $id . '.jpg"><div class="text-right mb-3"><small>view on imgur</small></div></a>';
        }
    }
    $text = preg_replace_array($pattern, $replacements, $text);
    return $text;
}

/**
 * laravel polyfill
 */
if (!function_exists('preg_replace_array')) {
    /**
     * Replace a given pattern with each value in the array in sequentially.
     *
     * @param string $pattern
     * @param array $replacements
     * @param string $subject
     * @return string
     */
    function preg_replace_array($pattern, array $replacements, $subject)
    {
        return preg_replace_callback($pattern, function () use (&$replacements) {
            foreach ($replacements as $key => $value) {
                return array_shift($replacements);
            }
        }, $subject);
    }
}

function cb_linkifySelective($matches)
{
    //error_log( count( $matches ) );
    //error_log( $matches[ 0 ] );
    //error_log( $matches[ 1 ] );
    //error_log( $matches[ 2 ] );
    //error_log( $matches[ 3 ] );
    //error_log( $matches[ 4 ] );
    //error_log( $matches[ 5 ] );

    $url = $matches[0];

    if (stripos($url, 'youtube-nocookie') !== false) {
        return $url; //    Ignore: these have been replaced above
    } elseif (stripos($url, 'www.twitch.tv') !== false) {
        return $url; //    Ignore: these have been replaced above
    } elseif (substr_compare($url, '.png', -4) === 0 || substr_compare($url, '.jpg', -4) === 0 || substr_compare($url,
            '.jpeg', -5) === 0) {
        return $url; //    Ignore: this is an image!
    } else {
        $actualURL = $url;
        //if( strpos( $url, 'www' ) === 0 )
        //    $actualURL = "http://" . $url; //    Prepend http://

        if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
            $actualURL = "https://" . $url; //    Prepend http://
        }

        return '<a onmouseover=" Tip( \'' . $url . '\' ) " onmouseout=\'UnTip()\' href=\'' . $actualURL . '\'>' . $url . '</a>';
    }
}

function linkifyBasicURLs($text)
{
    //$pattern = '@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@';
    //$pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
    //$pattern = '@((?<=[^\"\'])https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@';     //    NOT preceded by ' or "
    //$pattern = '@([^\'\"]https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@';     //    NOT preceded by ' or " - 22:33 22/02/2014
    //    http://stackoverflow.com/questions/833469/regular-expression-for-url
    //$pattern = "(\s)((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)";
    //$pattern = '((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)';

    // meleu: commented this in 31-May-2018
    //$pattern = '(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,63}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?';
    //$text = preg_replace_callback( '/' . $pattern . '/', 'cb_linkifySelective', $text );

    // meleu: applying some tricks I learned in
    // https://stackoverflow.com/questions/12538358/
    // NOTE: using '~' instead of '/' to enclose the regex
    $text = preg_replace(
        '~(https?://[a-z0-9_./?=&#%:+(),-]+)(?![^<>]*>)~i',
        ' <a href="$1" target="_blank" rel="noopener">$1</a> ',
        $text);
    $text = preg_replace(
        '~(\s|^)(www\.[a-z0-9_./?=&#%:+(),-]+)(?![^<>]*>)~i',
        ' <a target="_blank" href="https://$2" rel="noopener">$2</a> ',
        $text);

    return $text;
}

/**
 * @param      $commentIn
 * @param bool $withImgur imgur url parsing requires the links to reliably point to mp4s - can't be static images
 * @return null|string|string[]
 */
function parseTopicCommentPHPBB($commentIn, $withImgur = false)
{
    //    Parse and format tags
    $comment = $commentIn;

    //    [url]
    //$comment = preg_replace( '/(\\[url=http:\\/\\/)(.*?)(\\])(.*?)(\\[\\/url\\])/i', '<a onmouseover=" Tip( \'${2}\' ) " onmouseout=\'UnTip()\' href=\'http://${2}\'>${4}</a>', $comment );
    //$comment = preg_replace( '/(\\[url=)(.*?)(\\])(.*?)(\\[\\/url\\])/i', '<a onmouseover=" Tip( \'${2}\' ) " onmouseout=\'UnTip()\' href=\'http://${2}\'>${4}</a>', $comment );
    //

    // NOTE: using '~' instead of '/' to enclose the regex
    $comment = preg_replace(
        '~\[url=(https?://[^\]]+)\](.*?)(\[/url\])~i',
        '<a onmouseover=" Tip( \'$1\' )" onmouseout=\'UnTip()\' href=\'$1\'>$2</a>',
        $comment);
    $comment = preg_replace(
        '~\[url=([^\]]+)\](.*?)(\[/url\])~i',
        '<a onmouseover=" Tip( \'$1\' )" onmouseout=\'UnTip()\' href=\'https://$1\'>$2</a>',
        $comment);

    //    [b]
    $comment = preg_replace('/\\[b\\](.*?)\\[\\/b\\]/i', '<b>${1}</b>', $comment);
    //    [i]
    $comment = preg_replace('/\\[i\\](.*?)\\[\\/i\\]/i', '<i>${1}</i>', $comment);
    //    [s]
    $comment = preg_replace('/\\[s\\](.*?)\\[\\/s\\]/i', '<s>${1}</s>', $comment);

    //    [img]
    $comment = preg_replace('/(\\[img=)(.*?)(\\])/i', '<img class=\'injectinlineimage\' src=\'${2}\' />', $comment);
    //    [ach]
    $comment = preg_replace_callback('/(\\[ach=)(.*?)(\\])/i', 'cb_injectAchievementPHPBB', $comment);
    //    [user]
    $comment = preg_replace_callback('/(\\[user=)(.*?)(\\])/i', 'cb_injectUserPHPBB', $comment);
    //    [game]
    $comment = preg_replace_callback('/(\\[game=)(.*?)(\\])/i', 'cb_injectGamePHPBB', $comment);
    //    [video]
    //error_log( $comment );

    $comment = linkifyYouTubeURLs($comment);
    $comment = linkifyTwitchURLs($comment);
    if ($withImgur) {
        $comment = linkifyImgurURLs($comment);
    }
    $comment = linkifyBasicURLs($comment);

    //global $autolink;
    //$comment = $autolink->convert( $comment );
    //    Debug:
    //$comment = $commentIn . "<br/>" . $comment;

    return $comment;
}

//////////////////////////////////////////////////////////////////////////////////////////
//    PHPBB/Tooltip Rendering
//////////////////////////////////////////////////////////////////////////////////////////
//    17:04 18/04/2013
function WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltipText)
{
    $displayable = str_replace("'", '&#39;', $displayable);
    $tooltipText = str_replace("'", '\\\'', $tooltipText);

    $tooltip = "<div id='objtooltip'>" .
        "<img src='$tooltipImagePath' width='$tooltipImageSize' height='$tooltipImageSize' />" .
        "<b>$tooltipTitle</b><br/>$tooltipText" .
        "</div>";

    $tooltip = str_replace('<', '&lt;', $tooltip);
    $tooltip = str_replace('>', '&gt;', $tooltip);

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "$displayable" .
        "</div>";
}

function GetGameAndTooltipDiv(
    $gameID,
    $gameName,
    $gameIcon,
    $consoleName,
    $justImage = false,
    $imgSizeOverride = 32,
    $justText = false
) {
    $gameNameStr = str_replace("'", "\'", $gameName);

    $tooltipIconSize = 64; //96;

    $consoleStr = '';
    if ($consoleName !== null && strlen($consoleName) > 2) {
        $consoleStr = "($consoleName)";
    }

    $gameIcon = $gameIcon != null ? $gameIcon : "/Images/PlayingIcon32.png";

    $tooltip = "<div id=\'objtooltip\'>" .
        "<img src=\'$gameIcon\' width=\'$tooltipIconSize\' height=\'$tooltipIconSize\' />" .
        "<b>$gameNameStr</b><br/>" .
        "$consoleStr" .
        "</div>";

    $tooltip = str_replace('<', '&lt;', $tooltip);
    $tooltip = str_replace('>', '&gt;', $tooltip);
    //echo $tooltip;
    //$tooltip = str_replace( "'", "\\'", $tooltip );
    //echo $tooltip;

    $displayable = "";

    if ($justText == false) {
        $displayable = "<img alt=\"$gameName\" title=\"$gameName\" src='" . getenv('APP_STATIC_URL') . "$gameIcon' width='$imgSizeOverride' height='$imgSizeOverride' class='badgeimg' />";
    }

    if ($justImage == false) {
        $displayable .= "$gameName $consoleStr";
    }

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/Game/$gameID'>" .
        "$displayable" .
        "</a>" .
        "</div>";
}

//    17:05 18/04/2013
function GetUserAndTooltipDiv(
    $user,
    $points,
    $motto,
    $lastActivity,
    $lastActivityAt,
    $imageInstead,
    $customLink = null,
    $iconSizeDisplayable = 32,
    $iconClassDisplayable = 'badgeimg'
) {
    $tooltipIconSize = 128; //96;

    $tooltip = "<div id=\'objtooltip\'>";

    $tooltip .= "<table><tbody>";
    $tooltip .= "<tr><td class=\'fixedtooltipcolleft\'><img src=\'/UserPic/" . $user . ".png\' width=\'$tooltipIconSize\' height=\'$tooltipIconSize\' /></td>";

    $tooltip .= "<td class=\'fixedtooltipcolright\'>";
    $tooltip .= "<b>$user</b>";

    if ($points !== null) {
        $tooltip .= "&nbsp;($points)";
    }

    if ($motto !== null && strlen($motto) > 2) {
        $motto = str_replace('\'', '\\\'', $motto);
        $motto = str_replace('"', '\\\'\\\'', $motto);
        $tooltip .= "</br><span class=\'usermotto tooltip\'>$motto</span>";
    }

    if ($lastActivity !== null) {
        $lastActAtNiceDate = getNiceDate(strtotime($lastActivityAt));
        $tooltip .= "<br/>Last seen: $lastActAtNiceDate<br/>$user $lastActivity";
    }

    $tooltip .= "</td>";
    $tooltip .= "</tr>";
    $tooltip .= "</tbody></table>";
    $tooltip .= "</div>";

    $tooltip = htmlspecialchars($tooltip);
    //$tooltip = str_replace( '<', '&lt;', $tooltip );
    //$tooltip = str_replace( '>', '&gt;', $tooltip );

    $linkURL = "/User/$user";
    if (isset($customLink)) {
        $linkURL = $customLink;
    }

    $displayable = $user;
    if ($imageInstead == true) {
        $displayable = "<img src='/UserPic/$user" . ".png' width='$iconSizeDisplayable' height='$iconSizeDisplayable' alt='$user' title='$user' class='$iconClassDisplayable' />";
    }

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='$linkURL'>" .
        "$displayable" .
        "</a>" .
        "</div>";
}

//    17:05 18/04/2013
function GetAchievementAndTooltipDiv(
    $achID,
    $achName,
    $achDesc,
    $achPoints,
    $gameName,
    $badgeName,
    $inclSmallBadge = false,
    $smallBadgeOnly = false,
    $extraText = '',
    $smallBadgeSize = 32,
    $imgclass = 'badgeimg'
) {
    $tooltipIconSize = 64; //96;

    $achNameStr = str_replace("'", "\'", $achName);
    $achDescStr = str_replace("'", "\'", $achDesc);
    $gameNameStr = str_replace("'", "\'", $gameName);

    $tooltip = "<div id=\'objtooltip\'>" .
        "<img src=\'" . getenv('APP_STATIC_URL') . "/Badge/$badgeName" . ".png\' width=\'$tooltipIconSize\' height=\'$tooltipIconSize\' />" .
        "<b>$achNameStr ($achPoints)</b><br/>" .
        "<i>($gameNameStr)</i><br/>" .
        "<br/>" .
        "$achDescStr<br/>" .
        "$extraText" .
        "</div>";

    $tooltip = str_replace('<', '&lt;', $tooltip);
    $tooltip = str_replace('>', '&gt;', $tooltip);

    $smallBadge = '';
    $displayable = "$achName ($achPoints)";

    if ($inclSmallBadge) {
        $smallBadgePath = "/Badge/$badgeName" . ".png";
        $smallBadge = "<img width='$smallBadgeSize' height='$smallBadgeSize' src=\"" . getenv('APP_STATIC_URL') . "$smallBadgePath\" alt='$achNameStr' title='$achNameStr' class='$imgclass' />";

        if ($smallBadgeOnly) {
            $displayable = "";
        }
    }

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/Achievement/$achID'>" .
        "$smallBadge" .
        "$displayable" .
        //"$achName ($achPoints) ($gameName)" .
        "</a>" .
        "</div>";
}

//    11:24 27/06/2013
function GetLeaderboardAndTooltipDiv($lbID, $lbName, $lbDesc, $gameName, $gameIcon, $displayable)
{
    $tooltipIconSize = 64; //96;

    $lbNameStr = str_replace("'", "\'", $lbName);
    $lbDescStr = str_replace("'", "\'", $lbDesc);
    $gameNameStr = str_replace("'", "\'", $gameName);

    $tooltip = "<div id=\'objtooltip\'>" .
        "<img src=\'$gameIcon\' width=\'$tooltipIconSize\' height=\'$tooltipIconSize\ />" .
        "<b>$lbNameStr</b><br/>" .
        "<i>($gameNameStr)</i><br/>" .
        "<br/>" .
        "$lbDescStr<br/>" .
        "</div>";

    $tooltip = str_replace('<', '&lt;', $tooltip);
    $tooltip = str_replace('>', '&gt;', $tooltip);

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/leaderboardinfo.php?i=$lbID'>" .
        "$displayable" .
        "</a>" .
        "</div>";
}

function RenderThemeSelector()
{
    $dirContent = scandir('./css/');

    $cssFileList = array();
    foreach ($dirContent as $filename) {
        //echo $filename;
        $fileStart = strpos($filename, "rac_");
        if ($fileStart !== false) {
            $filename = substr($filename, $fileStart + 4);
            $filename = substr($filename, 0, strlen($filename) - 4);
            $cssFileList[] = $filename;
        }
    }


    //echo "<div class='themeselector' >";
    echo "<select id='themeselect' onchange='ResetTheme(); return false;'>";

    $currentCustomCSS = RA_ReadCookie('RAPrefs_CSS');
    foreach ($cssFileList as $nextCSS) {
        $cssFull = "/css/rac_" . $nextCSS . ".css";
        $selected = (strcmp($currentCustomCSS, $cssFull) == 0) ? 'selected' : '';
        echo "<option $selected value='$cssFull'>$nextCSS</option>";
    }

    // $cssList = Array();
    // $cssList['None'] = '/css/rac_blank.css';
    // $cssList['MLP'] = '/css/rac_pony.css';
    // $cssList['Red'] = '/css/rac_red.css';
    // $cssList['Green'] = '/css/rac_green.css';
    // $cssList['Mobile'] = '/css/rac_mobile.css';
    // $currentCustomCSS = RA_ReadCookie( 'RAPrefs_CSS' );
    // foreach( $cssList as $cssName => $cssURL )
    // {
    // $selected = ( strcmp( $currentCustomCSS, $cssURL ) == 0 ) ? 'selected' : '';
    // echo "<option $selected value='$cssURL'>$cssName</option>";
    // }

    echo "</select>";
    //echo "</div>";
}

// function RenderCarouselScript()
// {
// global $mobileBrowser;
// $carouselWidth = $mobileBrowser ? 220 : 480;
// echo "
// <script type='text/javascript'>
// jQuery(document).ready(function($)
// {
// function generatePages() {
// var _total, i, _link;
// _total = $( '#carousel' ).rcarousel( 'getTotalPages' );
// for ( i = 0; i < _total; i++ ) {
// _link = $( '<a href='#'></a>' );
// $(_link)
// .bind('click', {page: i},
// function( event ) {
// $( '#carousel' ).rcarousel( 'goToPage', event.data.page );
// event.preventDefault();
// }
// )
// .addClass( 'bullet off' )
// .appendTo( '#carouselpages' );
// }
// // mark first page as active
// $( 'a:eq(0)', '#carouselpages' )
// .removeClass( 'off' )
// .addClass( 'on' )
// .css( 'background-image', 'url(http://i.retroachievements.org/Images/page-on.png)' );
// $( '.newstitle' ).css( 'opacity', 0.0 ).delay( 500 ).fadeTo( 'slow', 1.0 );
// $( '.newstext' ).css( 'opacity', 0.0 ).delay( 900 ).fadeTo( 'slow', 1.0 );
// $( '.newsauthor' ).css( 'opacity', 0.0 ).delay( 1100 ).fadeTo( 'slow', 1.0 );
// }
// function pageLoaded( event, data ) {
// $( 'a.on', '#carouselpages' )
// .removeClass( 'on' )
// .css( 'background-image', 'url(http://i.retroachievements.org/Images/page-off.png)' );
// $( 'a', '#carouselpages' )
// .eq( data.page )
// .addClass( 'on' )
// .css( 'background-image', 'url(http://i.retroachievements.org/Images/page-on.png)' );
// }
// function onNext() {
// $( '.newstitle' ).css( 'opacity', 0.0 ).delay( 500 ).fadeTo( 'slow', 1.0 );
// $( '.newstext' ).css( 'opacity', 0.0 ).delay( 900 ).fadeTo( 'slow', 1.0 );
// $( '.newsauthor' ).css( 'opacity', 0.0 ).delay( 1100 ).fadeTo( 'slow', 1.0 );
// }
// function onPrev() {
// //alert( 'onPrev' );
// }
// $('#carousel').rcarousel(
// {
// visible: 1,
// step: 1,
// speed: 500,
// auto: {
// enabled: true,
// interval: 7000
// },
// width: $carouselWidth,
// height: 220,
// start: generatePages,
// pageLoaded: pageLoaded,
// onNext: onNext,
// onPrev: onPrev,
// }
// );
// $( '#ui-carousel-next' )
// .add( '#ui-carousel-prev' )
// .add( '.bullet' )
// .hover(
// function() {
// $( this ).css( 'opacity', 0.7 );
// },
// function() {
// $( this ).css( 'opacity', 1.0 );
// }
// )
// .click(
// function() {
// //alert( 'Handler for .click() called.' );
// //$( 'body' ).find( '.newstext' ).fadeTo( 0, 0 );
// $( '.newstitle' ).css( 'opacity', 0.0 ).delay( 500 ).fadeTo( 'slow', 1.0 );
// $( '.newstext' ).css( 'opacity', 0.0 ).delay( 900 ).fadeTo( 'slow', 1.0 );
// $( '.newsauthor' ).css( 'opacity', 0.0 ).delay( 1100 ).fadeTo( 'slow', 1.0 );
// $( '.wrapper' ).pixastic( 'desaturate' );
// }
// );
// //refreshOnlinePlayers();
// //setInterval( refreshOnlinePlayers, 1000*60 );
// refreshActivePlayers();
// setInterval( refreshActivePlayers, 1000*60 );
// });
// </script>
// ";
// }
// function RenderCarouselScript()
// {
// global $mobileBrowser;
// $carouselWidth = $mobileBrowser ? 220 : 480;
// echo "
// <script type='text/javascript'>
// jQuery(document).ready(function($)
// {
// function generatePages() {
// var _total, i, _link;
// _total = $( '#carousel' ).rcarousel( 'getTotalPages' );
// for ( i = 0; i < _total; i++ ) {
// _link = $( '<a href='#'></a>' );
// $(_link)
// .bind('click', {page: i},
// function( event ) {
// $( '#carousel' ).rcarousel( 'goToPage', event.data.page );
// event.preventDefault();
// }
// )
// .addClass( 'bullet off' )
// .appendTo( '#carouselpages' );
// }
// // mark first page as active
// $( 'a:eq(0)', '#carouselpages' )
// .removeClass( 'off' )
// .addClass( 'on' )
// .css( 'background-image', 'url(http://i.retroachievements.org/Images/page-on.png)' );
// $( '.newstitle' ).css( 'opacity', 0.0 ).delay( 500 ).fadeTo( 'slow', 1.0 );
// $( '.newstext' ).css( 'opacity', 0.0 ).delay( 900 ).fadeTo( 'slow', 1.0 );
// $( '.newsauthor' ).css( 'opacity', 0.0 ).delay( 1100 ).fadeTo( 'slow', 1.0 );
// }
// function pageLoaded( event, data ) {
// $( 'a.on', '#carouselpages' )
// .removeClass( 'on' )
// .css( 'background-image', 'url(http://i.retroachievements.org/Images/page-off.png)' );
// $( 'a', '#carouselpages' )
// .eq( data.page )
// .addClass( 'on' )
// .css( 'background-image', 'url(http://i.retroachievements.org/Images/page-on.png)' );
// }
// function onNext() {
// $( '.newstitle' ).css( 'opacity', 0.0 ).delay( 500 ).fadeTo( 'slow', 1.0 );
// $( '.newstext' ).css( 'opacity', 0.0 ).delay( 900 ).fadeTo( 'slow', 1.0 );
// $( '.newsauthor' ).css( 'opacity', 0.0 ).delay( 1100 ).fadeTo( 'slow', 1.0 );
// }
// function onPrev() {
// //alert( 'onPrev' );
// }
// $('#carousel').rcarousel(
// {
// visible: 1,
// step: 1,
// speed: 500,
// auto: {
// enabled: true,
// interval: 7000
// },
// width: $carouselWidth,
// height: 220,
// start: generatePages,
// pageLoaded: pageLoaded,
// onNext: onNext,
// onPrev: onPrev,
// }
// );
// $( '#ui-carousel-next' )
// .add( '#ui-carousel-prev' )
// .add( '.bullet' )
// .hover(
// function() {
// $( this ).css( 'opacity', 0.7 );
// },
// function() {
// $( this ).css( 'opacity', 1.0 );
// }
// )
// .click(
// function() {
// //alert( 'Handler for .click() called.' );
// //$( 'body' ).find( '.newstext' ).fadeTo( 0, 0 );
// $( '.newstitle' ).css( 'opacity', 0.0 ).delay( 500 ).fadeTo( 'slow', 1.0 );
// $( '.newstext' ).css( 'opacity', 0.0 ).delay( 900 ).fadeTo( 'slow', 1.0 );
// $( '.newsauthor' ).css( 'opacity', 0.0 ).delay( 1100 ).fadeTo( 'slow', 1.0 );
// $( '.wrapper' ).pixastic( 'desaturate' );
// }
// );
// //refreshOnlinePlayers();
// //setInterval( refreshOnlinePlayers, 1000*60 );
// refreshActivePlayers();
// setInterval( refreshActivePlayers, 1000*60 );
// });
// </script>
// ";
// }

function RenderCodeNotes($codeNotes)
{
    echo "<h3>Code Notes</h3>";
    echo "Code Notes found:";
    echo "<table class='smalltable xsmall'><tbody>";

    echo "<tr><th style='font-size:100%;'>Mem</th><th style='font-size:100%;'>Note</th><th style='font-size:100%;'>Author</th></tr>";

    foreach ($codeNotes as $nextCodeNote) {
        if (empty(trim($nextCodeNote['Note']))) {
            continue;
        }

        echo "<tr>";

        $addr = $nextCodeNote['Address'];
        $addrInt = hexdec($addr);

        $addrFormatted = sprintf("%04x", $addrInt);
        $memNote = nl2br($nextCodeNote['Note']);

        echo "<td style='width: 25%;'>";
        echo "<code>0x$addrFormatted</code>";
        echo "</td>";

        echo "<td>";
        echo "<div style='word-break:break-word;'>$memNote</div>";
        echo "</td>";

        echo "<td>";
        echo GetUserAndTooltipDiv($nextCodeNote['User'], null, null, null, null, true);
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";
}

function RenderMostPopularTitles($daysRange = 7, $offset = 0, $count = 10)
{
    $historyData = GetMostPopularTitles($daysRange, $offset, $count);

    echo "<div id='populargames' class='component' >";

    echo "<h3>Most Popular This Week</h3>";
    echo "<div id='populargamescomponent'>";

    echo "<table class='smalltable'><tbody>";
    echo "<tr><th colspan='2'>Game</th><th>Times Played</th></tr>";

    $numItems = count($historyData);
    for ($i = 0; $i < $numItems; $i++) {
        $nextData = $historyData[$i];
        $nextID = $nextData['ID'];
        $nextTitle = $nextData['Title'];
        $nextIcon = $nextData['ImageIcon'];
        $nextConsole = $nextData['ConsoleName'];

        echo "<tr>";

        echo "<td class='gameimage'>";
        echo GetGameAndTooltipDiv($nextID, $nextTitle, $nextIcon, $nextConsole, true, 32, false);
        echo "</td>";

        echo "<td class='gametext'>";
        echo GetGameAndTooltipDiv($nextID, $nextTitle, $nextIcon, $nextConsole, false, 32, true);
        echo "</td>";

        echo "<td class='sumtotal'>";
        echo $nextData['PlayedCount'];
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";

    echo "</div>";
}
