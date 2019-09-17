<?php 
    require_once __DIR__ . '/../lib/bootstrap.php';

    RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

    // Fetch topic ID
    $requestedTopicID = seekGET( 't', 0 );
    settype( $requestedTopicID, "integer" );

    if( $requestedTopicID == 0 )
    {
        header( "location: " . getenv('APP_URL') . "/forum.php?e=unknowntopic" );
        exit;
    }

    getTopicDetails( $requestedTopicID, $topicData );
    // temporary workaround to fix some game's forum topics
    //if( getTopicDetails( $requestedTopicID, $topicData ) == FALSE )
    //{
        //header( "location: " . getenv('APP_URL') . "/forum.php?e=unknowntopic2" );
        //exit;
    //}

    if( $permissions < $topicData['RequiredPermissions'] )
    {
        header( "location: " . getenv('APP_URL') . "/forum.php?e=nopermission" );
        exit;
    }

    // Fetch other params
    $count = 15;
    $offset = seekGET( 'o', 0 );
    settype( $offset, "integer" );
    settype( $count, "integer" );

    // Fetch 'goto comment' param if available
    $gotoCommentID = seekGET( 'c', NULL );
    if( isset( $gotoCommentID ) )
    {
        // Override $offset, just find this comment and go to it.
        getTopicCommentCommentOffset( $requestedTopicID, $gotoCommentID, $count, $offset );
    }

    // Fetch comments
    $commentList = getTopicComments( $requestedTopicID, $offset, $count, $numTotalComments );

    // We CANNOT have a topic with no comments... this doesn't make sense.
    if( $commentList == NULL || count($commentList) == 0 )
    {
        header( "location: " . getenv('APP_URL') . "/forum.php?e=unknowntopic3" );
        exit;
    }

    $thisTopicID = $topicData['ID'];
    settype( $thisTopicID, 'integer' );
    //$thisTopicID = $requestedTopicID; //??!?
    $thisTopicAuthor = $topicData['Author'];
    $thisTopicAuthorID = $topicData['AuthorID'];
    $thisTopicCategory = $topicData['Category'];
    $thisTopicCategoryID = $topicData['CategoryID'];
    $thisTopicForum = $topicData['Forum'];
    $thisTopicForumID = $topicData['ForumID'];
    $thisTopicTitle = $topicData['TopicTitle'];
    $thisTopicPermissions = $topicData['RequiredPermissions'];

    $pageTitle = "View topic: $thisTopicForum - $thisTopicTitle";

    $errorCode = seekGET('e');

    RenderDocType();
?>

<head>
    <?php RenderSharedHeader( $user ); ?>
    <?php RenderTitleTag( $pageTitle, $user ); ?>
    <?php RenderGoogleTracking(); ?>
</head>

<body>
<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
    <?php RenderErrorCodeWarning( 'both', $errorCode ); ?>
    <div id="forums" class="both">

        <?php
        echo "<div class='navpath'>";
        echo "<a href='/forum.php'>Forum Index</a>";
        echo " &raquo; <a href='forum.php?c=$thisTopicCategoryID'>$thisTopicCategory</a>";
        echo " &raquo; <a href='viewforum.php?f=$thisTopicForumID'>$thisTopicForum</a>";
        echo " &raquo; <b>$thisTopicTitle</b></a>";
        echo "</div>";

        echo "<h2 class='longheader'>$thisTopicTitle</h2>";

        //if( isset( $user ) && $permissions >= 1 )
        if( isset( $user ) && ( $thisTopicAuthor == $user || $permissions >= \RA\Permissions::Admin ) )
        {
            echo "<div class='devbox'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(500); return false;\">Options (Click to show):</span><br/>";
            echo "<div id='devboxcontent'>";

            echo "<li>Change Topic Title:</li>";
            echo "<form action='requestmodifytopic.php' method='post' >";
            echo "<input type='text' name='v' value='$thisTopicTitle' size='51' >";
            echo "<input type='hidden' name='t' value='$thisTopicID' />";
            echo "<input type='hidden' name='f' value='" . ModifyTopicField::ModifyTitle . "' />";
            echo "&nbsp;";
            echo "<input type='submit' name='submit' value='Submit' size='37' />";
            echo "</form>";

            if( $permissions >= \RA\Permissions::Admin )
            {
                echo "<li>Delete Topic:</li>";
                echo "<form action='requestmodifytopic.php' method='post' >";
                echo "<input type='hidden' name='v' value='$thisTopicID' size='51' >";
                echo "<input type='hidden' name='t' value='$thisTopicID' />";
                echo "<input type='hidden' name='f' value='" . ModifyTopicField::DeleteTopic . "' />";
                echo "&nbsp;";
                echo "<input type='submit' name='submit' value='Delete Permanently' size='37' />";
                echo "</form>";

                $selected0 = ( $thisTopicPermissions == 0 ) ? 'selected' : '';
                $selected1 = ( $thisTopicPermissions == 1 ) ? 'selected' : '';
                $selected2 = ( $thisTopicPermissions == 2 ) ? 'selected' : '';
                $selected3 = ( $thisTopicPermissions == 3 ) ? 'selected' : '';
                $selected4 = ( $thisTopicPermissions == 4 ) ? 'selected' : '';

                echo "<li>Restrict Topic:</li>";
                echo "<form action='requestmodifytopic.php' method='post' >";
                echo "<select name='v'>";
                echo "<option value='0' $selected0>Unregistered</option>";
                echo "<option value='1' $selected1>Registered</option>";
                echo "<option value='2' $selected2>Super User</option>";
                echo "<option value='3' $selected3>Developer</option>";
                echo "<option value='4' $selected4>Admin</option>";
                echo "</select>";
                echo "<input type='hidden' name='t' value='$thisTopicID' />";
                echo "<input type='hidden' name='f' value='" . ModifyTopicField::RequiredPermissions . "' />";
                echo "&nbsp;";
                echo "<input type='submit' name='submit' value='Change Minimum Permissions' size='37' />";
                echo "</form>";
            }

            // TBD: Report offensive content
            // TBD: Subscribe to this topic
            // TBD: Make top-post wiki
            // if( ( $thisTopicAuthor == $user ) || $permissions >= 3 )
            // {
                // echo "<li>Delete Topic!</li>";
                // echo "<form action='requestmodifytopic.php' >";
                // echo "<input type='hidden' name='i' value='$thisTopicID' />";
                // echo "<input type='hidden' name='f' value='1' />";
                // echo "&nbsp;";
                // echo "<input type='submit' name='submit' value='Delete Permanently' size='37' />";
                // echo "</form>";
            // }

            echo "</div>";
            echo "</div>";
        }

        echo "<table class='smalltable'><tbody>";

        if( $numTotalComments > $count )
        {
            echo "<tr>";

            echo "<td class='forumpagetabs' colspan='2'>";
            echo "<div class='forumpagetabs'>";

            echo "Page:&nbsp;";
            $pageOffset = ( $offset / $count );
            $numPages = ceil( $numTotalComments / $count );

            if( $pageOffset > 0 )
            {
                $prevOffs = $offset - $count;
                echo "<a class='forumpagetab' href='/viewtopic.php?t=$requestedTopicID&amp;o=$prevOffs'>&lt;</a> ";
            }

            for( $i = 0; $i < $numPages; $i++ )
            {
                $nextOffs = $i * $count;
                $pageNum = $i+1;

                if( $nextOffs == $offset )
                    echo "<span class='forumpagetab current'>$pageNum</span> ";
                else
                    echo "<a class='forumpagetab' href='/viewtopic.php?t=$requestedTopicID&amp;o=$nextOffs'>$pageNum</a> ";
            }

            if( $offset + $count < $numTotalComments )
            {
                $nextOffs = $offset + $count;
                echo "<a class='forumpagetab' href='/viewtopic.php?t=$requestedTopicID&amp;o=$nextOffs'>&gt;</a> ";
            }

            echo "</div>";
            echo "</td>";
            echo "</tr>";
        }

        echo "<tr class='topiccommentsheader'>";
        echo "<th>Author</th>";
        echo "<th>Message</th>";
        echo "</tr>";

        // Output all topics, and offer 'prev/next page'
        foreach( $commentList as $commentData )
        {
            //var_dump( $commentData );

            // Output one forum, then loop
            $nextCommentID = $commentData['ID'];
            $nextCommentPayload = $commentData['Payload'];
            $nextCommentAuthor = $commentData['Author'];
            $nextCommentAuthorID = $commentData['AuthorID'];
            $nextCommentDateCreated = $commentData['DateCreated'];
            $nextCommentDateModified = $commentData['DateModified'];
            $nextCommentAuthorised = $commentData['Authorised'];

            if( $nextCommentDateCreated !== NULL )
                $nextCommentDateCreatedNiceDate = date( "d M, Y H:i", strtotime( $nextCommentDateCreated ) );
            else
                $nextCommentDateCreatedNiceDate = "None";

            if( $nextCommentDateModified !== NULL )
                $nextCommentDateModifiedNiceDate = date( "d M, Y H:i", strtotime( $nextCommentDateModified ) );
            else
                $nextCommentDateModifiedNiceDate = "None";

            $showDisclaimer = false;
            $showAuthoriseTools = false;

            if( $nextCommentAuthorised == 0 )
            {
                // Allow, only if this is MY comment (disclaimer: unofficial), or if I'm admin (disclaimer: unofficial, verify user?)
                if( $permissions >= \RA\Permissions::Developer )
                {
                    // Allow with disclaimer
                    $showDisclaimer = true;
                    $showAuthoriseTools = true;
                }
                else if( $nextCommentAuthor == $user )
                {
                    // Allow with disclaimer
                    $showDisclaimer = true;
                }
                else
                {
                    continue;    // Ignore this comment for the rest
                }
            }

            if( isset( $gotoCommentID ) && $nextCommentID == $gotoCommentID )
                echo "<tr class='highlight'>";
            else
                echo "<tr>";

            echo "<td class='commentavatar'>";
            echo GetUserAndTooltipDiv( $nextCommentAuthor, FALSE, NULL, 64 );
            echo "</br>";
            echo GetUserAndTooltipDiv( $nextCommentAuthor, TRUE, NULL, 64 );
            echo "</td>";

            echo "<td class='commentpayload'>";

            echo "<div class='smalltext rightfloat'>Posted: $nextCommentDateCreatedNiceDate";

            if( ( $user == $nextCommentAuthor ) || ( $permissions >= \RA\Permissions::Admin ) )
                echo "&nbsp;<a href='/editpost.php?c=$nextCommentID'>(Edit&nbsp;Post)</a>";

            if( $showDisclaimer )
            {
                echo "<br/><span class='hoverable' title='Unverified: not yet visible to the public. Please wait for a moderator to authorise this comment.'>(Unverified)</span>";
                if( $showAuthoriseTools )
                {
                    echo "<br/><a href='requestupdateuser.php?t=$nextCommentAuthor&amp;p=1&amp;v=1'>Authorise this user and all their posts?</a>";
                    echo "<br/><a href='requestupdateuser.php?t=$nextCommentAuthor&amp;p=1&amp;v=0'>Permanently Block (spam)?</a>";
                }
            }

            if( $nextCommentDateModified !== NULL )
                echo "<br/>Last Edit: $nextCommentDateModifiedNiceDate</div>";
            echo "</div>";

            echo "<div class='topiccommenttext'>";
            RenderTopicCommentPayload( $nextCommentPayload );
            echo "</div>";
            echo "</td>";

            echo "</tr>";
        }

        if( $numTotalComments > $count )
        {
            echo "<tr>";

            echo "<td class='forumpagetabs' colspan='2'>";
            echo "<div class='forumpagetabs'>";

            echo "Page:&nbsp;";
            $pageOffset = ( $offset / $count );
            $numPages = ceil( $numTotalComments / $count );

            if( $pageOffset > 0 )
            {
                $prevOffs = $offset - $count;
                echo "<a class='forumpagetab' href='/viewtopic.php?t=$requestedTopicID&amp;o=$prevOffs'>&lt;</a> ";
            }

            for( $i = 0; $i < $numPages; $i++ )
            {
                $nextOffs = $i * $count;
                $pageNum = $i+1;

                if( $nextOffs == $offset )
                    echo "<span class='forumpagetab current'>$pageNum</span> ";
                else
                    echo "<a class='forumpagetab' href='/viewtopic.php?t=$requestedTopicID&amp;o=$nextOffs'>$pageNum</a> ";
            }

            if( $offset + $count < $numTotalComments )
            {
                $nextOffs = $offset + $count;
                echo "<a class='forumpagetab' href='/viewtopic.php?t=$requestedTopicID&amp;o=$nextOffs'>&gt;</a> ";
            }

            echo "</div>";
            echo "</td>";

            echo "</tr>";
        }

        if( $user !== NULL && $user !== "" && $thisTopicID != 0 )
        {
            echo "<tr>";

            echo "<td class='commentavatar'>";
            echo GetUserAndTooltipDiv( $user, FALSE, NULL, 64 );
            echo "</br>";
            echo GetUserAndTooltipDiv( $user, TRUE, NULL, 64 );
            echo "</td>";

            echo "<td class='fullwidth'>";

            RenderPHPBBIcons();

            $defaultMessage = ( $permissions >= 1 ) ? "" : "** Your account appears to be locked. Did you confirm your email? **";
            $inputEnabled = ( $permissions >= 1 ) ? "" : "disabled";
            
            echo "<form action='requestsubmittopiccomment.php' method='post'>";
            echo "<textarea id='commentTextarea' class='fullwidth forum' rows='10' cols='63' $inputEnabled maxlength='60000' name='p'>$defaultMessage</textarea><br/><br/>";
            echo "<input type='hidden' name='u' value='$user'>";
            echo "<input type='hidden' name='t' value='$thisTopicID'>";
            echo "<input style='float: right' type='submit' value='Submit' $inputEnabled size='37'/>";    // TBD: replace with image version
            echo "</form>";

            echo "</td>";

            echo "</tr>";

            echo "</tbody></table>";

            //echo "<div class=\"posteddate\">Posted: $nextCommentDateCreatedNiceDate</div>";
            //echo "<div class=\"usercommenttext\">";
            //RenderTopicCommentPayload( $nextCommentPayload );
            //echo "</div>";
            //echo "</td>";
            echo "</tr>";

            echo "</tbody></table>";
        }
        else
        {
            echo "</tbody></table>";
            RenderLoginComponent( $user, $points, $errorCode, TRUE );
        }

        ?>
        <br/>
    </div> 
</div>
  
<?php RenderFooter(); ?>

</body>
</html>
