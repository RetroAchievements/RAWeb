<?php
require_once( __DIR__ . '/../bootstrap.php' );
abstract class UserPref
{
    const EmailOn_ActivityComment = 0;
    const EmailOn_AchievementComment = 1;
    const EmailOn_UserWallComment = 2;
    const EmailOn_ForumReply = 3;
    const EmailOn_AddFriend = 4;
    const EmailOn_PrivateMessage = 5;
    const EmailOn_Newsletter = 6;
    const EmailOn_unused2 = 7;
    const SiteMsgOn_ActivityComment = 8;
    const SiteMsgOn_AchievementComment = 9;
    const SiteMsgOn_UserWallComment = 10;
    const SiteMsgOn_ForumReply = 11;
    const SiteMsgOn_AddFriend = 12;

}

abstract class FBUserPref
{
    const PostFBOn_EarnAchievement = 0;
    const PostFBOn_CompleteGame = 1;
    const PostFBOn_UploadAchievement = 2;

}

//////////////////////////////////////////////////////////////////////////////////////////
//	Accounts
//////////////////////////////////////////////////////////////////////////////////////////
function mail_utf8( $to, $from_user, $from_email, $subject = '(No subject)', $message = '' )
{
    $from_user = "=?UTF-8?B?" . base64_encode( $from_user ) . "?=";
    $subject = "=?UTF-8?B?" . base64_encode( $subject ) . "?=";
    $headers = "From: $from_user <$from_email>\r\n" .
            "Reply-To: $from_user <$from_email>\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-type: text/html; charset=UTF-8\r\n";

    return mail( $to, $subject, $message, $headers, "-f" . $from_email );
}

//	20:18 21/03/2013
function sendValidationEmail( $user, $email )
{
    //	This generates and stores (and returns) a new email validation string in the DB.
    $strValidation = generateEmailValidationString( $user );
    $strEmailLink = getenv('APP_URL')."/validateEmail.php?v=$strValidation";

    //$subject = "RetroAchievements.org - Confirm Email: $user";
    $subject = "Welcome to RetroAchievements.org, $user";

    $msg = "You or someone using your email address has attempted to sign up for an account at <a href='".getenv('APP_URL')."'>RetroAchievements.org</a><br/>" .
            "<br/>" .
            "If this was you, please click the following link to confirm this email address and complete sign up:<br/>" .
            "<br/>" .
            "&nbsp; &nbsp; &nbsp; &nbsp; <a href='$strEmailLink'>$strEmailLink</a><br/>" .
            "<br/>" .
            "If this wasn't you, please ignore this email.<br/>" .
            "<br/>" .
            "Thanks! And hope to see you on the forums!<br/>" .
            "<br/>" .
            "-- Your friends at <a href='".getenv('APP_URL')."'>RetroAchievements.org</a><br/>";

    error_log( __FUNCTION__ . " sending mail to $user at address $email" );

    $retVal = mail_utf8( $email, "RetroAchievements.org", "noreply@retroachievements.org", $subject, $msg );

    error_log( __FUNCTION__ . " return val: $retVal" );

    return $retVal;
}

//	16:08 17/04/2013
function generateEmailValidationString( $user )
{
    $emailCookie = rand_string( 16 );
    $expiry = time() + 60 * 60 * 24 * 7;

    $query = "INSERT INTO EmailConfirmations VALUES( '$user', '$emailCookie', $expiry )";
    log_sql( $query );
    $dbResult = s_mysql_query( $query );
    if( $dbResult == FALSE )
    {
        log_sql_fail();
        return FALSE;
    }

    //	Clear permissions til they validate their email.
    SetAccountPermissionsJSON( 'Scott', \RA\Permissions::Admin, $user, 0 );

    return $emailCookie;
}

function SetAccountPermissionsJSON( $sourceUser, $sourcePermissions, $destUser, $newPermissions )
{
    $retVal = array();
    $destPermissions = getUserPermissions( $destUser );

    $retVal[ 'Success' ] = TRUE;
    $retVal[ 'DestUser' ] = $destUser;
    $retVal[ 'DestPrevPermissions' ] = $destPermissions;
    $retVal[ 'NewPermissions' ] = $newPermissions;

    if( $destPermissions > $sourcePermissions )
    {
        //	Ignore: this person cannot be demoted by a lower-level member
        error_log( __FUNCTION__ . " failed: $sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Not allowed!" );
        $retVal[ 'Error' ] = "$sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Not allowed!";
        $retVal[ 'Success' ] = FALSE;
    }
    else if( ( $newPermissions >= \RA\Permissions::Admin ) && ( $sourcePermissions != \RA\Permissions::Root ) )
    {
        //	Ignore: cannot promote to admin unless you are root
        error_log( __FUNCTION__ . " failed: $sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Changing to admin requires Root account ('Scott')!" );
        $retVal[ 'Error' ] = "$sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Changing to admin requires Root account ('Scott')!";
        $retVal[ 'Success' ] = FALSE;
    }
    else
    {
        $query = "UPDATE UserAccounts SET Permissions = $newPermissions WHERE User='$destUser'";
        log_sql( $query );
        $dbResult = s_mysql_query( $query );
        if( $dbResult == FALSE )
        {
            //	Unrecognised user?
            error_log( __FUNCTION__ . " failed: $sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Cannot find user: '$destUser'!" );
            $retVal[ 'Error' ] = "$sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Cannot find user: '$destUser'!";
            $retVal[ 'Success' ] = FALSE;
        }
        else
        {
            error_log( __FUNCTION__ . " success: $sourceUser ($sourcePermissions) changed $destUser ($destPermissions) to $newPermissions." );
        }
    }

    return $retVal;
}

//	16:08 17/04/2013
function setAccountPermissions( $sourceUser, $sourcePermissions, $user, $permissions )
{
    $existingPermissions = getUserPermissions( $user );
    if( $existingPermissions > $sourcePermissions )
    {
        //	Ignore: this person cannot be demoted by a lower-level member
        error_log( __FUNCTION__ . " failed: $sourceUser ($sourcePermissions) is trying to set $user ($existingPermissions) to $permissions??! not allowed!" );
        return FALSE;
    }
    else if( ( $permissions >= \RA\Permissions::Admin ) && ( $sourceUser != 'Scott' ) )
    {
        error_log( __FUNCTION__ . " failed: person who is not Scott trying to set a user's permissions to admin" );
        return FALSE;
    }
    else
    {
        $query = "UPDATE UserAccounts SET Permissions = $permissions WHERE User='$user'";
        log_sql( $query );
        $dbResult = s_mysql_query( $query );
        if( $dbResult !== FALSE )
        {
            return TRUE;
        }
        else
        {
            //	Unrecognised  user
            error_log( __FUNCTION__ . " failed: cannot update $user in UserAccounts??! $user, $permissions" );
            return FALSE;
        }
    }
}

function setAccountForumPostAuth( $sourceUser, $sourcePermissions, $user, $permissions )
{
    //	$sourceUser is setting $user's forum post permissions.

    if( $permissions == 0 )
    {
        //	This user is a spam user: remove all their posts and set their account as banned.
        $query = "UPDATE UserAccounts SET ManuallyVerified = $permissions WHERE User='$user'";
        log_sql( $query );
        $dbResult = s_mysql_query( $query );
        if( $dbResult !== FALSE )
        {
            //	Also ban the spammy user!
            RemoveUnauthorisedForumPosts( $user );

            SetAccountPermissionsJSON( $sourceUser, $sourcePermissions, $user, \RA\Permissions::Spam );
            return TRUE;
        }
        else
        {
            //	Unrecognised  user
            error_log( __FUNCTION__ . " failed: cannot update $user in UserAccounts??! $user, $permissions" );
            return FALSE;
        }
    }
    else if( $permissions == 1 )
    {
        $query = "UPDATE UserAccounts SET ManuallyVerified = $permissions WHERE User='$user'";
        log_sql( $query );
        $dbResult = s_mysql_query( $query );
        if( $dbResult !== FALSE )
        {
            AuthoriseAllForumPosts( $user );

            error_log( __FUNCTION__ . " SUCCESS! Upgraded $user to allow forum posts, authorised by $sourceUser ($sourcePermissions)" );
            return TRUE;
        }
        else
        {
            //	Unrecognised  user
            error_log( __FUNCTION__ . " failed: cannot update $user in UserAccounts??! $user, $permissions" );
            return FALSE;
        }
    }
    else //	?
    {
        //	Unrecognised stuff
        error_log( __FUNCTION__ . " failed: cannot update $user in UserAccounts??! $user, $permissions" );
        return FALSE;
    }
}

function validateEmailValidationString( $emailCookie, &$user )
{
    $query = "SELECT * FROM EmailConfirmations WHERE EmailCookie='$emailCookie'";
    $dbResult = s_mysql_query( $query );

    if( $dbResult !== FALSE )
    {
        if( mysqli_num_rows( $dbResult ) == 1 )
        {
            $data = mysqli_fetch_assoc( $dbResult );
            $user = $data[ 'User' ];
            $query = "DELETE FROM EmailConfirmations WHERE User='$user' AND EmailCookie='$emailCookie'";
            log_sql( $query );
            $dbResult = s_mysql_query( $query );
            if( $dbResult !== FALSE )
            {
                $response = SetAccountPermissionsJSON( 'Scott', \RA\Permissions::Admin, $user, 1 );
                //if( setAccountPermissions( 'Scott', \RA\Permissions::Admin, $user, 1 ) )
                if( $response[ 'Success' ] )
                {
                    static_addnewregistereduser( $user );
                    generateAPIKey( $user );
                    error_log( __FUNCTION__ . " SUCCESS: validated email address for $user" );
                    return TRUE;
                }
                else
                {
                    log_sql_fail();
                    error_log( __FUNCTION__ . " failed: cant set user's permissions to 1?? $user, $emailCookie - " . $response[ 'Error' ] );
                    return FALSE;
                }
            }
            else
            {
                //	Error!
                log_sql_fail();
                error_log( $query );
                error_log( __FUNCTION__ . " failed: can't remove the email confirmation we just found??! $user, $emailCookie" );
                return FALSE;
            }
        }
        else
        {
            //	Unrecognised cookie or user
            error_log( $query );
            error_log( __FUNCTION__ . " failed: $emailCookie num rows found:" . mysqli_num_rows( $dbResult ) );
            return FALSE;
        }
    }
    else
    {
        //	Unrecognised db query
        error_log( $query );
        error_log( __FUNCTION__ . " failed: $emailCookie !$dbResult" );
        return FALSE;
    }
}

function sendFriendEmail( $user, $email, $type, $friend )
{
    settype( $type, 'integer' );
    error_log( __FUNCTION__ . " $user, $email, $type, $friend" );

    if( $user == $friend )
    {
        error_log( __FUNCTION__ . "not sending mail: what is happening... ( $user == $friend )" );
        return FALSE;
    }

    $emailTitle = '';
    $link = '';
    $emailReason = '';

    if( $type == 0 ) //	Requesting to be your friend
    {
        $emailTitle = "New Friend Request from $friend";
        $emailReason = "sent you a friend request";
        $link = "<a href='".getenv('APP_URL')."/User/$friend'>here</a>";
    }
    else if( $type == 1 ) //	Friend request confirmed
    {
        $emailTitle = "New Friend confirmed: $friend";
        $emailReason = "confirmed your friend request";
        $link = "<a href='".getenv('APP_URL')."/User/$friend'>here</a>";
    }
    else
    {
        error_log( __FUNCTION__ . " bad times..." );
        return FALSE; //	must break early! No nonsense emails please!
    }

    $msg = "Hello $user!<br/>" .
            "$friend on RetroAchievements has $emailReason!<br/>" .
            "Click $link to visit their user page!<br/>" .
            "<br/>" .
            "Thanks! And hope to see you on the forums!<br/>" .
            "<br/>" .
            "-- Your friends at RetroAchievements.org<br/>";

    if( IsAtHome() )
    {
        error_log( __FUNCTION__ . " dumping mail, not sending... no mailserver!" );
        error_log( $email );
        error_log( $emailTitle );
        error_log( $msg );
        $retVal = TRUE;
    }
    else
    {
        error_log( __FUNCTION__ . " sending friend mail to $user at address $email" );
        $retVal = mail_utf8( $email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg );
        error_log( __FUNCTION__ . " return val: $retVal" );
    }

    return $retVal;
}

function sendActivityEmail( $user, $email, $actID, $activityCommenter, $articleType, $threadInvolved = NULL, $altURLTarget = NULL )
{
    if( $user == $activityCommenter )
    {
        error_log( __FUNCTION__ . " not sending mail: I wrote this! ($user == $activityCommenter)" );
        return FALSE;
    }

    $emailTitle = '';
    $link = '';
    $activityDescription = '';


    if( $articleType == 1 ) //	Game (wall)
    {
        $emailTitle = "New Game Wall Comment from $activityCommenter";
        $link = "<a href='".getenv('APP_URL')."/Game/$actID'>here</a>";
        $activityDescription = "A game wall discussion you've commented in";
    }
    else if( $articleType == 2 ) //	Achievement: sending mail to person who authored an achievement
    {
        $emailTitle = "New Achievement Comment from $activityCommenter";
        $link = "<a href='".getenv('APP_URL')."/achievement/$actID'>here</a>";
        $activityDescription = "An achievement you created";
        if( isset( $threadInvolved ) )
            $activityDescription = "An achievement page discussion you've commented in";
    }
    else if( $articleType == 3 ) //	User (wall)
    {
        $emailTitle = "New User Wall Comment from $activityCommenter";
        $link = "<a href='".getenv('APP_URL')."/User/$altURLTarget'>here</a>";
        $activityDescription = "Your user wall";
        if( isset( $threadInvolved ) )
            $activityDescription = "A user wall discussion you've commented in";
    }
    else if( $articleType == 6 ) //	Forum thread
    {
        $emailTitle = "New Forum Comment from $activityCommenter";
        $link = "<a href='".getenv('APP_URL')."/$altURLTarget'>here</a>";
        $activityDescription = "A forum thread you've commented in";
    }
    else if( $articleType == 7 ) //	Ticket
    {
        $emailTitle = "New Ticket Comment from $activityCommenter";
        $link = "<a href='".getenv('APP_URL')."/ticketmanager.php?i=$actID'>here</a>";
        $activityDescription = "A ticket you've reported";
        if( isset( $threadInvolved ) )
            $activityDescription = "A ticket you've commented on";
    }
    else //if( $articleType == 5 )	//	Activity
    {
        //	Also used for Generic text:
        $emailTitle = "New Activity Comment from $activityCommenter";
        $link = "<a href='".getenv('APP_URL')."/feed.php?a=$actID'>here</a>";
        $activityDescription = "Your latest activity";
        if( isset( $threadInvolved ) )
            $activityDescription = "A thread you've commented in";
    }

    $msg = "Hello $user!<br/>" .
            "$activityDescription on RetroAchievements has received<br/>" .
            "a new comment from $activityCommenter. Click $link to see what they have written!<br/>" .
            "<br/>" .
            "Thanks! And hope to see you on the forums!<br/>" .
            "<br/>" .
            "-- Your friends at RetroAchievements.org<br/>";

    if( IsAtHome() )
    {
        error_log( __FUNCTION__ . " dumping mail, not sending... no mailserver!" );
        error_log( $email );
        error_log( $emailTitle );
        error_log( $msg );
        $retVal = TRUE;
    }
    else
    {
        error_log( __FUNCTION__ . " sending activity mail to $user at address $email" );
        $retVal = mail_utf8( $email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg );
        error_log( __FUNCTION__ . " return val: $retVal" );
    }

    return $retVal;
}

function SendPrivateMessageEmail( $user, $email, $title, $contentIn, $fromUser )
{
    if( $user == $fromUser )
    {
        error_log( __FUNCTION__ . " not sending mail: I wrote this! ($user == $fromUser)" );
        return FALSE;
    }

    $content = stripslashes( nl2br( $contentIn ) );

    //	Also used for Generic text:
    $emailTitle = "New Private Message from $fromUser";
    $link = "<a href='".getenv('APP_URL')."/inbox.php'>here</a>";

    $msg = "Hello $user!<br/>" .
            "You have received a new private message from $fromUser.<br/><br/>" .
            "Title: $title<br/>" .
            "$content<br/><br/>" .
            "Click $link to reply!<br/>" .
            "Thanks! And hope to see you on the forums!<br/>" .
            "<br/>" .
            "-- Your friends at RetroAchievements.org<br/>";

    if( IsAtHome() )
    {
        error_log( __FUNCTION__ . " dumping mail, not sending... no mailserver!" );
        error_log( $email );
        error_log( $emailTitle );
        error_log( $msg );
        $retVal = TRUE;
    }
    else
    {
        error_log( __FUNCTION__ . " sending activity mail to $user at address $email" );
        $retVal = mail_utf8( $email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg );
        error_log( __FUNCTION__ . " return val: $retVal" );
    }

    return $retVal;
}

function SendPasswordResetEmail( $user, $email, $token )
{
    $emailTitle = "Password Reset Request";
    $link = "<a href='".getenv('APP_URL')."/resetPassword.php?u=$user&amp;t=$token'>Confirm Your Email Address</a>";

    $msg = "Hello $user!<br/>" .
            "Your account has requested a password reset:<br/>" .
            "$link<br/>" .
            "Thanks!<br/>" .
            "-- Your friends at RetroAchievements.org<br/>";

    if( IsAtHome() )
    {
        error_log( __FUNCTION__ . " dumping mail, not sending... no mailserver!" );
        error_log( "Email: " . $email . ", Title: " . $emailTitle . ", Msg: " . $msg );
        $retVal = TRUE;
    }
    else
    {
        error_log( __FUNCTION__ . " sending activity mail to $user at address $email" );
        $retVal = mail_utf8( $email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg );
        error_log( __FUNCTION__ . " return val: $retVal" );
    }

    return $retVal;
}

function generateCookie( $user, &$cookie )
{
    if( !isset( $user ) || $user == FALSE )
    {
        error_log( __FUNCTION__ . " failed: user:$user" );
        return FALSE;
    }
    //	Attempt to set a cookie on the server: if successful, set it locally.

    $cookie = rand_string( 16 );

    $query = "UPDATE UserAccounts SET cookie='$cookie' WHERE User='$user'";

    log_sql( $query );
    $result = s_mysql_query( $query );
    if( $result !== FALSE )
    {
        RA_SetCookie( 'RA_User', $user );
        RA_SetCookie( 'RA_Cookie', $cookie );
        return true;
    }
    else
    {
        error_log( __FUNCTION__ . " failed: cannot update DB: $query" );
        RA_ClearCookie( 'RA_User' );
        return false;
    }
}

function generateAppToken( $user, &$tokenOut )
{
    if( !isset( $user ) || $user == FALSE )
    {
        error_log( __FUNCTION__ . " failed: user:$user" );
        return FALSE;
    }
    //	Attempt to set a token on the server: if successful, provide it.

    $newToken = rand_string( 16 );

    $expDays = 30;
    $expiryStr = date( "Y-m-d H:i:s", (time() + 60 * 60 * 24 * $expDays ) );
    $query = "UPDATE UserAccounts SET appToken='$newToken', appTokenExpiry='$expiryStr' WHERE User='$user'";

    log_sql( $query );
    $result = s_mysql_query( $query );
    if( $result !== FALSE )
    {
        $tokenOut = $newToken;
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}

function login_appWithToken( $user, $pass, &$tokenInOut, &$scoreOut, &$messagesOut )
{
    //error_log( __FUNCTION__ . "user:$user, pass:$pass, tokenInOut:$tokenInOut" );

    if( !isset( $user ) || $user == FALSE || strlen( $user ) < 2 )
    {
        error_log( __FUNCTION__ . " username failed: user:$user, pass:$pass, tokenInOut:$tokenInOut" );
        return 0;
    }

    $passwordProvided = ( isset( $pass ) && strlen( $pass ) >= 1 );
    $tokenProvided = ( isset( $tokenInOut ) && strlen( $tokenInOut ) >= 1 );

    if( $passwordProvided )
    {
        //	Password provided:
        //	Note: Safer to receive a plaintext password: embedding any DB secret in-app is inexcusable!
        $saltedPass = md5( $pass . getenv('RA_PASSWORD_SALT') );
        $query = "SELECT RAPoints, appToken FROM UserAccounts WHERE User='$user' AND SaltedPass='$saltedPass'";
        //error_log( $query );
    }
    else if( $tokenProvided )
    {
        //	Token provided:
        $query = "SELECT RAPoints, appToken, appTokenExpiry FROM UserAccounts WHERE User='$user' AND appToken='$tokenInOut'";
    }
    else
    {
        error_log( __FUNCTION__ . " token and pass failed: user:$user, pass:$pass, tokenInOut:$tokenInOut" );
        return 0;
    }

    //error_log( $query );
    $dbResult = s_mysql_query( $query );

    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        if( $data !== FALSE && mysqli_num_rows( $dbResult ) == 1 )
        {
            //	Test for expired tokens!
            if( $tokenProvided )
            {
                $expiry = $data[ 'appTokenExpiry' ];
                if( time() > strtotime( $expiry ) )
                {
                    //	Expired!
                    error_log( __FUNCTION__ . " failed6: user:$user, tokenInOut:$tokenInOut, $expiry, " . strtotime( $expiry ) );
                    return -1;
                }
            }

            $scoreOut = $data[ 'RAPoints' ];
            settype( $scoreOut, "integer" );
            $messagesOut = GetMessageCount( $user, $totalMessageCount );

            //if( $passwordProvided )
            //	generateAppToken( $user, $tokenInOut );
            //	Against my better judgement... ##SD
            if( strlen( $data[ 'appToken' ] ) != 16 )   //	Generate if new
            {
                generateAppToken( $user, $tokenInOut );  //
            }
            else
            {
                //	Return old token if not
                $tokenInOut = $data[ 'appToken' ];

                //	Update app token expiry now anyway

                $expDays = 30;
                $expiryStr = date( "Y-m-d H:i:s", (time() + 60 * 60 * 24 * $expDays ) );
                $query = "UPDATE UserAccounts SET appTokenExpiry='$expiryStr' WHERE User='$user'";
                log_sql( $query );
                s_mysql_query( $query );
            }

            postActivity( $user, \RA\ActivityType::Login, "" );

            return 1;
        }
        else
        {
            error_log( __FUNCTION__ . " failed5: user:$user, pass:$pass, tokenInOut:$tokenInOut" );
            return 0;
        }
    }
    else
    {
        error_log( __FUNCTION__ . " failed4: user:$user, pass:$pass, tokenInOut:$tokenInOut" );
        return 0;
    }
}

function getUserAppToken( $user )
{
    $query = "SELECT appToken FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        return $data[ 'appToken' ];
    }

    return "";
}

//	08:23 04/11/2014
function GetUserData( $user )
{
    $query = "SELECT * FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query( $query );

    if( $dbResult == FALSE || mysqli_num_rows( $dbResult ) != 1 )
    {
        error_log( $query );
        error_log( __FUNCTION__ . " failed: Achievement $id doesn't exist!" );
        return NULL;
    }
    else
    {
        return mysqli_fetch_assoc( $dbResult );
    }
}

function getAccountDetails( &$user, &$dataOut )
{
    if( !isset( $user ) || strlen( $user ) < 2 )
    {
        error_log( __FUNCTION__ . " failed: user:$user" );
        return false;
    }

    $query = "SELECT ID, cookie, User, EmailAddress, Permissions, RAPoints, TrueRAPoints, fbUser, fbPrefs, websitePrefs, LastActivityID, Motto, ContribCount, ContribYield, APIKey, UserWallActive, Untracked
				FROM UserAccounts
				WHERE User='$user'";

    $dbResult = s_mysql_query( $query );
    if( $dbResult == false || mysqli_num_rows( $dbResult ) !== 1 )
    {
        error_log( __FUNCTION__ . " failed: user:$user, query:$query" );
        return false;
    }
    else
    {
        $dataOut = mysqli_fetch_array( $dbResult );
        $user = $dataOut[ 'User' ];    //	Fix case!
        return true;
    }
}

function getAccountDetailsFB( $fbUser, &$details )
{
    $query = "SELECT User, EmailAddress, Permissions, RAPoints FROM UserAccounts WHERE fbUser='$fbUser'";
    $result = s_mysql_query( $query );
    if( $result == FALSE || mysqli_num_rows( $dbResult ) !== 1 )
    {
        error_log( __FUNCTION__ . " failed: fbUser:$fbUser, query:$query" );
        return FALSE;
    }
    else
    {
        $details = mysqli_fetch_array( $result );
        return TRUE;
    }
}

function changePassword( $user, $pass )
{
    //	Add salt
    $saltedHash = md5( $pass . getenv('RA_PASSWORD_SALT') );

    if( strrchr( ' ', $saltedHash ) || strlen( $saltedHash ) != 32 )
    {
        error_log( __FUNCTION__ . " failed: new pass $pass contains invalid characters or is not 32 chars long!" );
        return FALSE;
    }

    $query = "UPDATE UserAccounts SET SaltedPass='$saltedHash' WHERE user='$user'";
    log_sql( $query );
    if( s_mysql_query( $query ) == TRUE )
    {
        return TRUE;
    }
    else
    {
        error_log( $query );
        error_log( __FUNCTION__ . " failed: user:$user" );
        return FALSE;
    }
}

function associateFB( $user, $fbUser )
{
    //	TBD: Sanitise!
    $query = "UPDATE UserAccounts SET fbUser='$fbUser' WHERE User='$user'";
    //echo $query;
    log_sql( $query );
    if( s_mysql_query( $query ) == FALSE )
    {
        error_log( $query );
        error_log( __FUNCTION__ . " failed: user:$user and fbUser:$fbUser passed" );
        return FALSE;
    }
    else
    {
        // $query = "UPDATE UserAccounts SET fbPrefs=1 WHERE User='$user'";
        // log_sql( $query );
        // if( s_mysql_query( $query ) == FALSE )
        // {
        // error_log( $query );
        // error_log( __FUNCTION__ . " failed2: user:$user and fbUser:$fbUser passed" );
        // return FALSE;
        // }
    }

    //	Give them a badge :)
    AddSiteAward( $user, 5, 0 );

    return TRUE;
}

function getFBUser( $user, &$fbUserOut )
{
    $query = "SELECT fbUser FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query( $query );

    if( $dbResult == FALSE || mysqli_num_rows( $dbResult ) !== 1 )
    {
        error_log( __FUNCTION__ . " failed: user:$user" );
        return FALSE;
    }
    else
    {
        $db_entry = mysqli_fetch_assoc( $dbResult );
        $fbUserOut = $db_entry[ 'fbUser' ];
        return TRUE;
    }
}

function getUserIDFromUser( $user )
{
    $query = "SELECT ID FROM UserAccounts WHERE User LIKE '$user'";
    $dbResult = s_mysql_query( $query );

    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        return $data[ 'ID' ];
    }
    else
    {
        error_log( __FUNCTION__ . " cannot find user $user." );
        return 0;
    }
}

function getUserFromID( $userID )
{
    $query = "SELECT User FROM UserAccounts WHERE ID ='$userID'";
    $dbResult = s_mysql_query( $query );

    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        return $data[ 'User' ];
    }
    else
    {
        error_log( __FUNCTION__ . " cannot find user $user." );
        return "";
    }
}

function getUserMetadataFromID( $userID )
{
    $query = "SELECT * FROM UserAccounts WHERE ID ='$userID'";
    $dbResult = s_mysql_query( $query );

    if( $dbResult !== FALSE )
    {
        return mysqli_fetch_assoc( $dbResult );
    }
    else
    {
        error_log( __FUNCTION__ . " cannot find user $user." );
        return 0;
    }
}

function getUserStats( $user )
{

}

function getUserUnlockAchievement( $user, $achievementID, &$dataOut )
{
    $query = "SELECT ach.ID, aw.HardcoreMode, aw.Date
        FROM Achievements AS ach
        LEFT JOIN Awarded AS aw ON ach.ID = aw.AchievementID
        WHERE ach.ID = '$achievementID' AND aw.User = '$user'";

    $dbResult = s_mysql_query( $query );

    $dataOut = array();

    if( $dbResult === FALSE )
        return FALSE;

    while( $data = mysqli_fetch_assoc( $dbResult ) )
        $dataOut[] = $data;

    return count( $dataOut );
}

function getUserUnlocksDetailed( $user, $gameID, &$dataOut )
{
    $query = "SELECT ach.Title, ach.ID, ach.Points, aw.HardcoreMode
		FROM Achievements AS ach
		LEFT JOIN Awarded AS aw ON ach.ID = aw.AchievementID
		WHERE ach.GameID = '$gameID' AND aw.User = '$user'
		ORDER BY ach.ID ASC, aw.HardcoreMode ASC ";

    $dbResult = s_mysql_query( $query );

    $dataOut = array();

    if( $dbResult !== FALSE )
    {
        while( $data = mysqli_fetch_assoc( $dbResult ) )
            $dataOut[] = $data;
    }

    return count( $dataOut );
}

//	16:11 16/10/2014
function GetUserUnlocksData( $user, $gameID, $hardcoreMode )
{
    $query = "SELECT AchievementID
		FROM Achievements AS ach
		LEFT JOIN Awarded AS aw ON ach.ID = aw.AchievementID
		WHERE ach.GameID = '$gameID' AND aw.User = '$user' AND aw.HardcoreMode = $hardcoreMode ";

    $dbResult = s_mysql_query( $query );

    $retVal = array();
    while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
    {
        settype( $db_entry[ 'AchievementID' ], 'integer' );
        $retVal[] = $db_entry[ 'AchievementID' ];
    }

    return $retVal;
}

//	Deprecate
function getUserUnlocks( $user, $gameID, &$dataOut, $hardcoreMode )
{
    $dataOut = GetUserUnlocksData( $user, $gameID, $hardcoreMode );
    return count( $dataOut );
}

function getTopUsersByScore( $count, &$dataOut, $ofFriend = NULL )
{
    settype( $count, 'integer' );

    if( $count > 10 )
        $count = 10;

    $subquery = "WHERE !ua.Untracked";
    if( isset( $ofFriend ) )
    {
        //$subquery = "WHERE ua.User IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$ofFriend' )
        //			  OR ua.User = '$ofFriend' ";
        //	Only users whom I have added:
        $subquery = "WHERE !ua.Untracked AND ua.User IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$ofFriend' AND f.Friendship = 1 )";
    }

    $query = "SELECT User, RAPoints, TrueRAPoints
			  FROM UserAccounts AS ua
			  $subquery
			  ORDER BY RAPoints
			  DESC LIMIT 0, $count ";

    //echo $query;

    $dbResult = s_mysql_query( $query );

    if( $dbResult == FALSE || mysqli_num_rows( $dbResult ) == 0 )
    {
        //	This is acceptible if the user doesn't have any friends!
        //error_log( __FUNCTION__ . " failed: none found: count:$count query:$query" );
        return 0;
    }
    else
    {
        $i = 0;
        while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
        {
            //$dataOut[$i][0] = $db_entry["ID"];
            $dataOut[ $i ][ 1 ] = $db_entry[ "User" ];
            $dataOut[ $i ][ 2 ] = $db_entry[ "RAPoints" ];
            $dataOut[ $i ][ 3 ] = $db_entry[ "TrueRAPoints" ];
            $i++;
        }

        return $i;
    }
}

function getUserForumPostAuth( $user )
{
    $query = "SELECT uc.ManuallyVerified FROM UserAccounts AS uc WHERE uc.User = '$user'";
    $dbResult = s_mysql_query( $query );

    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        return $data[ 'ManuallyVerified' ];
    }
    else
    {
        error_log( $query );
        error_log( __FUNCTION__ . " issues! $userIn" );
        return $userIn;
    }
}

function correctUserCase( $userIn )
{
    $query = "SELECT uc.User FROM UserAccounts AS uc WHERE uc.User LIKE '$userIn'";
    $dbResult = s_mysql_query( $query );

    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        return $data[ 'User' ];
    }
    else
    {
        error_log( $query );
        error_log( __FUNCTION__ . " issues! $userIn" );
        return $userIn;
    }
}

//////////////////////////////////////////////////////////////////////////////////////////
//	Users/Voting
//////////////////////////////////////////////////////////////////////////////////////////
//	17:07 22/10/2014
function GetScore( $user )
{
    $query = "SELECT ua.RAPoints
			  FROM UserAccounts AS ua
			  WHERE ua.User='$user'";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $result = mysqli_fetch_assoc( $dbResult );
        $points = $result[ 'RAPoints' ];
        settype( $points, 'integer' );
        return $points;
    }
    else
    {
        error_log( __FUNCTION__ . " failed: user:$user" );
        return 0;
    }
}

//	19:52 02/02/2014s
function getUserRank( $user )
{
    $query = "  SELECT ( COUNT(*) + 1 ) AS UserRank
				FROM UserAccounts AS ua
				RIGHT JOIN UserAccounts AS ua2 ON ua.RAPoints < ua2.RAPoints
				WHERE ua.User = '$user'";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        return $data[ 'UserRank' ];
    }

    error_log( $query );
    error_log( __FUNCTION__ . " could not find $user in db?!" );

    return 0;
}

function updateAchievementVote( $achID, $posDiff, $negDiff )
{
    //	Tell achievement $achID that it's vote count has been changed by $posDiff and $negDiff

    $query = "UPDATE Achievements SET VotesPos=VotesPos+$posDiff, VotesNeg=VotesNeg+$negDiff WHERE ID=$achID";
    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        error_log( $query );
        return TRUE;
    }
    else
    {
        error_log( $query );
        error_log( __FUNCTION__ . " failed(;): achID:$achID, posDiff:$posDiff, negDiff:$negDiff" );
        return FALSE;
    }
}

function applyVote( $user, $achID, $vote )
{
    settype( $vote, 'integer' );
    if( $vote != 1 && $vote != -1 )
    {
        error_log( __FUNCTION__ . " failed: illegal vote:$vote by user:$user, achID:$achID" );
        return FALSE;
    }

    $posVote = ($vote == 1);
    $negVote = ($vote == -1);
    settype( $posVote, 'integer' );
    settype( $negVote, 'integer' );

    $query = "SELECT * FROM Votes WHERE User='$user' AND AchievementID='$achID'";
    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE && mysqli_num_rows( $dbResult ) == 0 )
    {
        //	Vote not yet cast - add it newly!
        $query = "INSERT INTO Votes VALUES ( '$user', '$achID', $vote )";
        log_sql( $query );
        $dbResult = s_mysql_query( $query );
        if( $dbResult !== FALSE )
        {
            return updateAchievementVote( $achID, $posVote, $negVote );
        }
        else
        {
            log_sql_fail();
            error_log( $query );
            error_log( __FUNCTION__ . " failed(;): user:$user, achID:$achID, vote:$vote" );
            return FALSE;
        }
    }
    else
    {
        //	Vote already cast: update it to the selected one.
        $data = mysqli_fetch_assoc( $dbResult );
        $voteAlreadyCast = $data[ 'Vote' ];
        if( $voteAlreadyCast == $vote )
        {
            //	Vote already posted... ignore?
            error_log( __FUNCTION__ . " warning: Identical vote already cast. ($user, $achID)" );
            return TRUE;
        }
        else
        {
            $query = "UPDATE Votes SET Vote=$vote WHERE User='$user' AND AchievementID='$achID'";

            $dbResult = s_mysql_query( $query );
            if( $dbResult !== FALSE )
            {
                //	Updated Votes. Now update ach:
                if( $vote == 1 && $voteAlreadyCast == -1 )
                {
                    //	Changing my vote to pos
                    return updateAchievementVote( $achID, 1, -1 );
                }
                else
                {
                    //	Changing my vote to neg
                    return updateAchievementVote( $achID, -1, 1 );
                }
            }
            else
            {
                error_log( $query );
                error_log( __FUNCTION__ . " failed(;): user:$user, achID:$achID, vote:$vote" );
                return FALSE;
            }
        }
    }
}

function getUserActivityRange( $user, &$firstLogin, &$lastLogin )
{
    //	00:45 27/02/2014 - removed redundant segments?
    $query = "SELECT MIN(act.timestamp) AS FirstLogin, MAX(act.timestamp) AS LastLogin
			  FROM Activity AS act
			  WHERE act.User = '$user' AND act.activitytype=2";

    // $query 	 = "SELECT act.timestamp, act.lastupdate FROM Activity AS act
    // WHERE act.ID = (
    // SELECT MAX(ID) FROM Activity AS act 	WHERE act.User = '$user' AND act.activitytype=2
    // )
    // OR	act.ID = (
    // SELECT MIN(ID) FROM Activity AS act 	WHERE act.User = '$user' AND act.activitytype=2
    // )
    // ORDER BY act.lastupdate ASC";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        $firstLogin = $data[ 'FirstLogin' ];
        $lastLogin = $data[ 'LastLogin' ];
        //$data = mysqli_fetch_assoc( $dbResult );
        //$lastLogin = $data['lastupdate'];

        return TRUE;
    }

    return FALSE;
}

//	03:29 22/03/2013
function getUserProgress( $user, $gameIDsCSV, &$dataOut )
{
    //	Create null entries so that we pass 'something' back.
    $gameIDsArray = explode( ',', $gameIDsCSV );
    foreach( $gameIDsArray as $gameID )
    {
        settype( $gameID, "integer" );
        $dataOut[ $gameID ][ 'NumPossibleAchievements' ] = 0;
        $dataOut[ $gameID ][ 'PossibleScore' ] = 0;
        $dataOut[ $gameID ][ 'NumAchieved' ] = 0;
        $dataOut[ $gameID ][ 'ScoreAchieved' ] = 0;
        $dataOut[ $gameID ][ 'NumAchievedHardcore' ] = 0;
        $dataOut[ $gameID ][ 'ScoreAchievedHardcore' ] = 0;
    }

    //	Count num possible achievements
    $query = "SELECT GameID, COUNT(*) AS AchCount, SUM(ach.Points) AS PointCount FROM Achievements AS ach
			  WHERE ach.Flags = 3 AND ach.GameID IN ( $gameIDsCSV )
			  GROUP BY ach.GameID
			  HAVING COUNT(*)>0 ";

    //error_log( $query );

    $dbResult = s_mysql_query( $query );
    if( $dbResult == FALSE )
    {
        error_log( __FUNCTION__ . "buggered up somehow. $user, $gameIDsCSV" );
        return 0;
    }

    while( $data = mysqli_fetch_assoc( $dbResult ) )
    {
        $dataOut[ $data[ 'GameID' ] ][ 'NumPossibleAchievements' ] = $data[ 'AchCount' ];
        $dataOut[ $data[ 'GameID' ] ][ 'PossibleScore' ] = $data[ 'PointCount' ];
        $dataOut[ $data[ 'GameID' ] ][ 'NumAchieved' ] = 0;
        $dataOut[ $data[ 'GameID' ] ][ 'ScoreAchieved' ] = 0;
        $dataOut[ $data[ 'GameID' ] ][ 'NumAchievedHardcore' ] = 0;
        $dataOut[ $data[ 'GameID' ] ][ 'ScoreAchievedHardcore' ] = 0;
    }

    //	Foreach return value from this, cross reference with 'earned' achievements. If not found, assume 0.
    //	Count earned achievements
    $query = "SELECT GameID, COUNT(*) AS AchCount, SUM( ach.Points ) AS PointCount, aw.HardcoreMode
			  FROM Awarded AS aw
			  LEFT JOIN Achievements AS ach ON aw.AchievementID = ach.ID
			  WHERE ach.GameID IN ( $gameIDsCSV ) AND ach.Flags = 3 AND aw.User = '$user'
			  GROUP BY aw.HardcoreMode, ach.GameID";

    $dbResult = s_mysql_query( $query );
    if( $dbResult == FALSE )
    {
        error_log( __FUNCTION__ . "buggered up in the second part. $user, $gameIDsCSV" );
        return 0;
    }

    while( $data = mysqli_fetch_assoc( $dbResult ) )
    {
        if( $data[ 'HardcoreMode' ] == 0 )
        {
            $dataOut[ $data[ 'GameID' ] ][ 'NumAchieved' ] = $data[ 'AchCount' ];
            $dataOut[ $data[ 'GameID' ] ][ 'ScoreAchieved' ] = $data[ 'PointCount' ];
        }
        else
        {
            $dataOut[ $data[ 'GameID' ] ][ 'NumAchievedHardcore' ] = $data[ 'AchCount' ];
            $dataOut[ $data[ 'GameID' ] ][ 'ScoreAchievedHardcore' ] = $data[ 'PointCount' ];
        }
    }

    return 0;
}

//	08:30 06/11/2014
function GetAllUserProgress( $user, $consoleID )
{
    $retVal = array();
    settype( $consoleID, 'integer' );

    //Title,
    $query = "SELECT ID, IFNULL( AchCounts.NumAch, 0 ) AS NumAch, IFNULL( MyAwards.NumIAchieved, 0 ) AS Earned, IFNULL( MyAwardsHC.NumIAchieved, 0 ) AS HCEarned
			FROM GameData AS gd
			LEFT JOIN (
				SELECT COUNT(ach.ID) AS NumAch, GameID
				FROM Achievements AS ach
				GROUP BY ach.GameID ) AchCounts ON AchCounts.GameID = gd.ID

			LEFT JOIN (
				SELECT gd.ID AS GameID, COUNT(*) AS NumIAchieved
				FROM Awarded AS aw
				LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
				LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
				WHERE aw.User = '$user' AND aw.HardcoreMode = 0
				GROUP BY gd.ID ) MyAwards ON MyAwards.GameID = gd.ID

			LEFT JOIN (
				SELECT gd.ID AS GameID, COUNT(*) AS NumIAchieved
				FROM Awarded AS aw
				LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
				LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
				WHERE aw.User = '$user' AND aw.HardcoreMode = 1
				GROUP BY gd.ID ) MyAwardsHC ON MyAwardsHC.GameID = gd.ID

			WHERE NumAch > 0 && gd.ConsoleID = $consoleID
			ORDER BY ID ASC";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $nextData = mysqli_fetch_assoc( $dbResult ) )
        {
            //	Auto:
            //$retVal[] = $nextData;
            //	Manual:
            $nextID = $nextData[ 'ID' ];
            unset( $nextData[ 'ID' ] );

            settype( $nextData[ 'NumAch' ], 'integer' );
            settype( $nextData[ 'Earned' ], 'integer' );
            settype( $nextData[ 'HCEarned' ], 'integer' );

            $retVal[ $nextID ] = $nextData;
        }
    }

    return $retVal;
}

//	15:56 05/11/2013
function getUsersGameList( $user, &$dataOut )
{
    $query = "SELECT gd.Title, c.Name AS ConsoleName, gd.ID, COUNT(AchievementID) AS NumAchieved
		FROM Awarded AS aw
		LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
		LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
		LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
		LEFT JOIN ( SELECT ach1.GameID AS GameIDInner, ach1.ID, COUNT(ach1.ID) AS TotalAch FROM Achievements AS ach1 GROUP BY GameID ) AS gt ON gt.GameIDInner = gd.ID
		WHERE aw.User = '$user'
		GROUP BY gd.ID";

    $dbResult = s_mysql_query( $query );
    if( $dbResult == FALSE )
    {
        log_email( __FUNCTION__ . " failed with $user" );

        error_log( __FUNCTION__ . "1 $user " );
        return 0;
    }

    $gamelistCSV = '0';

    while( $nextData = mysqli_fetch_assoc( $dbResult ) )
    {
        $dataOut[ $nextData[ 'ID' ] ] = $nextData;
        $gamelistCSV .= ', ' . $nextData[ 'ID' ];
    }

    //	Get totals:
    $query = "SELECT ach.GameID, gd.Title, COUNT(ach.ID) AS NumAchievements
			FROM Achievements AS ach
			LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
			WHERE ach.Flags = 3 AND ach.GameID IN ( $gamelistCSV )
			GROUP BY ach.GameID ";

    $dbResult = s_mysql_query( $query );
    if( $dbResult == FALSE )
    {
        log_email( $query );
        error_log( __FUNCTION__ . "2 $user " );
        return 0;
    }

    $i = 0;
    while( $nextData = mysqli_fetch_assoc( $dbResult ) )
    {
        $dataOut[ $nextData[ 'GameID' ] ][ 'Title' ] = $nextData[ 'Title' ];
        $dataOut[ $nextData[ 'GameID' ] ][ 'NumAchievements' ] = $nextData[ 'NumAchievements' ];
        $i++;
    }

    return $i;
}

//	12:56 22/03/2013
function getUsersRecentAwardedForGames( $user, $gameIDsCSV, $numAchievements, &$dataOut )
{
    $gameIDsArray = explode( ',', $gameIDsCSV );

    $numIDs = count( $gameIDsArray );
    if( $numIDs == 0 )
    {
        return;
    }

    //echo $numIDs;
    //error_log( $gameIDsCSV );

    $query = "SELECT ach.ID, ach.GameID, gd.Title AS GameTitle, ach.Title, ach.Description, ach.Points, ach.BadgeName, (!ISNULL(aw.User)) AS IsAwarded, aw.Date AS DateAwarded, (aw.HardcoreMode) AS HardcoreAchieved
			  FROM Achievements AS ach
			  LEFT OUTER JOIN Awarded AS aw ON aw.User = '$user' AND aw.AchievementID = ach.ID
			  LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
			  WHERE ach.Flags = 3 AND ach.GameID IN ( $gameIDsCSV )
			  ORDER BY IsAwarded DESC, HardcoreAchieved ASC, DateAwarded DESC, ach.DisplayOrder ASC, ach.ID ASC
			  LIMIT 5000";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== false )
    {
        while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
        {
            $dataOut[ $db_entry[ 'GameID' ] ][ $db_entry[ 'ID' ] ] = $db_entry;
        }
    }
    else
    {
        error_log( __FUNCTION__ . " something's gone wrong :( $user, $gameIDsCSV, $numAchievements" );
    }
}

//	12:55 22/03/2013
function getUserPageInfo( $user, &$libraryOut, $numGames, $numRecentAchievements, $localUser )
{
    $libraryOut = array();
    getUserActivityRange( $user, $firstLogin, $lastLogin );
    $libraryOut[ 'MemberSince' ] = $firstLogin;
    $libraryOut[ 'LastLogin' ] = $lastLogin;

    $libraryOut[ 'RecentlyPlayedCount' ] = getRecentlyPlayedGames( $user, 0, $numGames, $recentlyPlayedData );
    $libraryOut[ 'RecentlyPlayed' ] = $recentlyPlayedData;

    getAccountDetails( $user, $userInfo ); //	Necessary?

    $libraryOut[ 'ContribCount' ] = $userInfo[ 'ContribCount' ];
    $libraryOut[ 'ContribYield' ] = $userInfo[ 'ContribYield' ];
    $libraryOut[ 'TotalPoints' ] = $userInfo[ 'RAPoints' ];
    $libraryOut[ 'TotalTruePoints' ] = $userInfo[ 'TrueRAPoints' ];
    $libraryOut[ 'Permissions' ] = $userInfo[ 'Permissions' ];
    $libraryOut[ 'Untracked' ] = $userInfo[ 'Untracked' ];
    $libraryOut[ 'ID' ] = $userInfo[ 'ID' ];
    $libraryOut[ 'UserWallActive' ] = $userInfo[ 'UserWallActive' ];
    $libraryOut[ 'Motto' ] = htmlspecialchars( $userInfo[ 'Motto' ] );

    $libraryOut[ 'Rank' ] = getUserRank( $user ); //	ANOTHER call... can't we cache this?

    $numRecentlyPlayed = count( $recentlyPlayedData );

    if( $numRecentlyPlayed > 0 )
    {
        $gameIDsCSV = $recentlyPlayedData[ 0 ][ 'GameID' ];

        for( $i = 1; $i < $numRecentlyPlayed; $i++ )
        {
            $gameIDsCSV .= ", " . $recentlyPlayedData[ $i ][ 'GameID' ];
        }

        //echo $gameIDsCSV;

        getUserProgress( $user, $gameIDsCSV, $awardedData );

        $libraryOut[ 'Awarded' ] = $awardedData;

        getUsersRecentAwardedForGames( $user, $gameIDsCSV, $numRecentAchievements, $achievementData );

        $libraryOut[ 'RecentAchievements' ] = $achievementData;
    }

    $libraryOut[ 'Friendship' ] = 0;
    $libraryOut[ 'FriendReciprocation' ] = 0;

    if( isset( $localUser ) && ( $localUser != $user ) )
    {
        $query = "SELECT (f.User = '$localUser') AS Local, f.Friend, f.Friendship FROM Friends AS f
				  WHERE (f.User = '$localUser' && f.Friend = '$user')
				  UNION
				  SELECT (f.User = '$localUser') AS Local, f.Friend, f.Friendship FROM Friends AS f
				  WHERE (f.User = '$user' && f.Friend = '$localUser') ";

        //echo $query;

        $dbResult = s_mysql_query( $query );
        if( $dbResult !== FALSE )
        {
            while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
            {
                if( $db_entry[ 'Local' ] == 1 )
                    $libraryOut[ 'Friendship' ] = $db_entry[ 'Friendship' ];
                else //if( $db_entry['Local'] == 0 )
                    $libraryOut[ 'FriendReciprocation' ] = $db_entry[ 'Friendship' ];
            }
        }
        else
        {
            log_sql_fail();
        }
    }
}

//	20:44 02/02/2014
function getControlPanelUserInfo( $user, &$libraryOut )
{
    $libraryOut = array();
    $libraryOut[ 'Played' ] = array();
    //getUserActivityRange( $user, $firstLogin, $lastLogin );
    //$libraryOut['MemberSince'] = $firstLogin;
    //$libraryOut['LastLogin'] = $lastLogin;

    $query = "	SELECT gd.ID, c.Name AS ConsoleName, gd.Title AS GameTitle, COUNT(*) AS NumAwarded, Inner1.NumPossible
				FROM Awarded AS aw
				LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
				LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
				LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
				LEFT JOIN (
					SELECT ach.GameID, COUNT(*) AS NumPossible
					FROM Achievements AS ach
					GROUP BY ach.GameID ) AS Inner1 ON Inner1.GameID = gd.ID
				WHERE aw.User = '$user' AND aw.HardcoreMode = 0
				GROUP BY gd.ID
				ORDER BY ConsoleID, gd.Title";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
            $libraryOut[ 'Played' ][] = $db_entry;   //	use as raw array to preserve order!

        return TRUE;
    }
    else
    {
        error_log( __FUNCTION__ );
        error_log( $query );

        return FALSE;
    }
}

function getUserList( $sortBy, $offset, $count, &$dataOut, $requestedBy )
{
    return getUserListByPerms( $sortBy, $offset, $count, $dataOut, $requestedBy, NULL , FALSE );
}

function getUserListByPerms( $sortBy, $offset, $count, &$dataOut, $requestedBy , &$perms = NULL, $showUntracked = FALSE )
{
    settype( $offset, 'integer' );
    settype( $count, 'integer' );
    settype( $showUntracked, 'boolean' );


    $whereQuery = NULL;
    $permsFilter = NULL;

    settype( $perms, 'integer' );
    if( $perms >= \RA\Permissions::Spam && $perms <= \RA\Permissions::Unregistered || $perms == \RA\Permissions::SuperUser )
        $permsFilter = "ua.Permissions = $perms ";
    else if( $perms >= \RA\Permissions::Registered && $perms <= \RA\Permissions::Admin )
        $permsFilter = "ua.Permissions >= $perms ";
    else
    {
        if( $showUntracked ) // if reach this point, show only untracked users
            $whereQuery = "WHERE ua.Untracked ";
	else // perms invalid and do not show untracked? get outta here!
	    return 0;
    }

    if( $showUntracked )
    {
        if( $whereQuery == NULL )
            $whereQuery = "WHERE $permsFilter ";
    }
    else
        $whereQuery = "WHERE ( !ua.Untracked || ua.User = \"$requestedBy\" ) AND $permsFilter";


    settype( $sortBy, 'integer' );
    if( $sortBy < 1 || $sortBy > 6 )
        $sortBy = 1;

    switch( $sortBy )
    {
        case 1:
            //	Default sort:
            $orderBy = "ua.User ASC ";
            break;
        case 2:
            //	RAPoints
            $orderBy = "ua.RAPoints DESC ";
            break;
        case 3:
            //	NumAwarded
            $orderBy = "NumAwarded DESC ";
            break;
        case 4:
            //	Default sort: inverse
            $orderBy = "ua.User DESC ";
            break;
        case 5:
            //	RAPoints inverse
            $orderBy = "ua.RAPoints ASC ";
            break;
        case 6:
            //	NumAwarded inverse
            $orderBy = "NumAwarded ASC ";
            break;
    }

    $query = "	SELECT ua.ID, ua.User, ua.RAPoints, ua.TrueRAPoints, COUNT(aw.AchievementID) As NumAwarded, ua.LastLogin
				FROM UserAccounts AS ua
				LEFT JOIN Awarded AS aw ON aw.User=ua.User
                $whereQuery
				GROUP BY ua.User
				ORDER BY $orderBy
				LIMIT $offset, $count";

    $numFound = 0;

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
        {
            $dataOut[ $numFound ] = $db_entry;
            $numFound++;
        }
    }
    else
    {
        error_log( __FUNCTION__ );
        error_log( $query );
    }

    return $numFound;
}

function getUserPermissions( $user )
{
    if( $user == NULL )
        return 0;

    $query = "SELECT Permissions FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query( $query );
    if( $dbResult == FALSE )
    {
        error_log( __FUNCTION__ );
        error_log( $query );
        return 0;
    }
    else
    {
        $data = mysqli_fetch_assoc( $dbResult );
        return $data[ 'Permissions' ];
    }
}

function getUsersCompletedGamesAndMax( $user )
{
    $retVal = Array();

    if( !IsValidUsername( $user ) )
        return $retVal;

    $requiredFlags = 3;
    $minAchievementsForCompletion = 5;

    $query = "SELECT gd.ID AS GameID, c.Name AS ConsoleName, gd.ImageIcon, gd.Title, COUNT(ach.GameID) AS NumAwarded, inner1.MaxPossible, (COUNT(ach.GameID)/inner1.MaxPossible) AS PctWon, aw.HardcoreMode
		FROM Awarded AS aw
		LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
		LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
		LEFT JOIN
			( SELECT COUNT(*) AS MaxPossible, ach1.GameID FROM Achievements AS ach1 WHERE Flags = $requiredFlags GROUP BY GameID )
			AS inner1 ON inner1.GameID = ach.GameID AND inner1.MaxPossible > $minAchievementsForCompletion
		LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
		WHERE aw.User='$user' AND ach.Flags = $requiredFlags
		GROUP BY ach.GameID, aw.HardcoreMode
		ORDER BY PctWon DESC, inner1.MaxPossible DESC, gd.Title ";

    global $db;
    $dbResult = mysqli_query( $db, $query );

    $gamesFound = 0;
    if( $dbResult !== FALSE )
    {
        while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
        {
            $retVal[ $gamesFound ] = $db_entry;
            $gamesFound++;
        }
    }
    else
    {
        log_email( $query );
        log_email( "failing" );
    }

    return $retVal;
}

function getUsersSiteAwards( $user )
{
    $retVal = Array();

    if( !IsValidUsername( $user ) )
        return $retVal;

    $query = "
	(
	SELECT UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAt, saw.AwardType, saw.AwardData, saw.AwardDataExtra, gd.Title, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon
				  FROM SiteAwards AS saw
				  LEFT JOIN GameData AS gd ON ( gd.ID = saw.AwardData AND saw.AwardType = 1 )
				  LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
				  WHERE saw.AwardType = 1 AND saw.User = '$user'
				  GROUP BY saw.AwardType, saw.AwardData, saw.AwardDataExtra
	)
	UNION
	(
	SELECT UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAt, saw.AwardType, MAX( saw.AwardData ), saw.AwardDataExtra, NULL, NULL, NULL, NULL
				  FROM SiteAwards AS saw
				  WHERE saw.AwardType > 1 AND saw.User = '$user'
				  GROUP BY saw.AwardType
	)
	ORDER BY AwardedAt, AwardType, AwardDataExtra ASC";

    global $db;
    $dbResult = mysqli_query( $db, $query );

    $numFound = 0;
    if( $dbResult !== FALSE )
    {
        while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
        {
            $retVal[ $numFound ] = $db_entry;
            $numFound++;
        }
    }
    else
    {
        log_email( $query );
        log_email( "failing" );
    }

    return $retVal;
}

function AddSiteAward( $user, $awardType, $data, $dataExtra = 0 )
{
    settype( $awardType, 'integer' );
    //settype( $data, 'integer' );	//	nullable
    settype( $dataExtra, 'integer' );

    $query = "INSERT INTO SiteAwards VALUES( NOW(), '$user', '$awardType', '$data', '$dataExtra' ) ON DUPLICATE KEY UPDATE AwardDate = NOW();";
    log_sql( $query );
    global $db;
    $dbResult = mysqli_query( $db, $query );
    if( $dbResult != NULL )
    {
        //error_log( "AddSiteAward OK! $user, $awardType, $data" );
        //log_email( __FUNCTION__ . " $user, $awardType, $data" );
    }
    else
    {
        log_email( "Failed AddSiteAward: $query" );
    }
}

function GetDeveloperStats( $count, $type )
{
    if( $type == 1 )
    {
        $query = "SELECT ua.User as Author, ContribYield as NumCreated
				FROM UserAccounts AS ua
				WHERE ContribYield > 0
				ORDER BY ContribYield DESC
				LIMIT 0, $count";
    }
    else if( $type == 2 )
    {
        $query = "SELECT ua.User as Author, ContribCount as NumCreated
				FROM UserAccounts AS ua
				WHERE ContribCount > 0
				ORDER BY ContribCount DESC
				LIMIT 0, $count";
    }
    else
    {
        $query = "SELECT ach.Author, COUNT(*) as NumCreated
				FROM Achievements as ach
				WHERE ach.Flags = 3
				GROUP BY ach.Author
				ORDER BY NumCreated DESC
				LIMIT 0, $count";
    }

    $dbResult = s_mysql_query( $query );
    SQL_ASSERT( $dbResult );

    $retVal = array();
    while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
    {
        settype( $db_entry[ 'NumCreated' ], 'integer' );
        $retVal[] = $db_entry;
    }
    return $retVal;
}

function GetDeveloperStatsFull( $count, $sortBy )
{
    settype( $sortBy, 'integer' );
    settype( $count, 'integer' );

    switch( $sortBy )
    {
        case 0: // number of achievements
            $order = "Achievements";
            break;
        case 1: // number of points allocated
            $order = "ContribYield";
            break;
        case 2: // number of achievements won by others
            $order = "ContribCount";
            break;
        case 3: // number of achievements
            $order = "OpenTickets";
            break;
        default:
            $order = "Achievements";
    }

    $query = "
    SELECT
        ua.User as Author,
        ContribCount,
        ContribYield,
        COUNT(ach.ID) as Achievements,
        COUNT(tick.ID) as OpenTickets
    FROM
        UserAccounts AS ua
    LEFT JOIN
        Achievements AS ach ON (ach.Author = ua.User AND ach.Flags = 3)
    LEFT JOIN
        Ticket AS tick ON (tick.AchievementID = ach.ID AND tick.ReportState = 1)
    WHERE
        ContribCount > 0 AND ContribYield > 0
    GROUP BY
        ua.User
    ORDER BY
        $order DESC,
        OpenTickets ASC
    LIMIT 0, $count";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $nextData = mysqli_fetch_assoc( $dbResult ) )
            $retVal[] = $nextData;
    }
    else
    {
        error_log( __FUNCTION__ . " failed?! $offset, $limit" );
    }

    return $retVal;
}

function GetUserFields( $username, $fields )
{
    $fieldsCSV = implode( ",", $fields );
    $query = "SELECT $fieldsCSV FROM UserAccounts AS ua
              WHERE ua.User = '$username'";
    //error_log( $query );
    $dbResult = s_mysql_query( $query );
    return mysqli_fetch_assoc( $dbResult );
}

function RemovePasswordResetToken( $username, $passwordResetToken )
{
    global $db;

    $query = "UPDATE UserAccounts AS ua "
            . "WHERE ua.User='$user' "
            . "SET ua.PasswordResetToken = ''";

    $dbResult = s_mysql_query( $query );
    return mysqli_affected_rows( $db ) == 1;
}

function IsValidPasswordResetToken( $usernameIn, $passwordResetToken )
{
    global $db;

    $retVal = [];

    if( strlen( $passwordResetToken ) == 20 )
    {
        $query = "SELECT * FROM UserAccounts AS ua "
                . "WHERE ua.User='$usernameIn' AND ua.PasswordResetToken='$passwordResetToken'";

        $dbResult = s_mysql_query( $query );
        SQL_ASSERT( $dbResult );

        if( mysqli_num_fields( $dbResult ) == 1 )
        {
            //	Success; delete old token
            //RemovePasswordResetToken( $usernameIn, $passwordResetToken );
            $retVal[ 'Success' ] = TRUE;
        }
        else
        {
            $retVal[ 'Error' ] = "Incorrect token.";
            $retVal[ 'Success' ] = FALSE;
        }
    }
    else
    {
        $retVal[ 'Error' ] = "Token looks to be invalid. Must be 20 characters.";
        $retVal[ 'Success' ] = FALSE;
    }

    return $retVal;
}

function RequestPasswordReset( $usernameIn )
{
    global $db;

    $retVal = [];

    $userFields = GetUserFields( mysqli_real_escape_string( $db, $usernameIn ), [ "User", "EmailAddress" ] );
    if( $userFields == NULL )
    {
        $retVal[ 'Error' ] = "Could not find $usernameIn";
        return $retVal;
    }

    $username = $userFields[ "User" ];
    $emailAddress = $userFields[ "EmailAddress" ];

    $newToken = rand_string( 20 );

    $query = "UPDATE UserAccounts AS ua
              SET ua.PasswordResetToken = '$newToken'
              WHERE ua.User='$username'";

    $dbResult = s_mysql_query( $query );
    SQL_ASSERT( $dbResult );

    SendPasswordResetEmail( $username, $emailAddress, $newToken );

    $retVal[ 'Success' ] = TRUE;

    return $retVal;
}

//  Returns true if Patreon Badge exists
function HasPatreonBadge( $usernameIn )
{
    $query = "SELECT * FROM SiteAwards AS sa "
            . "WHERE sa.AwardType = 6 AND sa.User = '$usernameIn'";

    $dbResult = s_mysql_query( $query );
    return mysqli_num_rows( $dbResult ) > 0;
}

//
function SetPatreonSupporter( $usernameIn, $enable )
{
    if( $enable )
    {
        $query = "INSERT INTO SiteAwards VALUES( NOW(), '$usernameIn', '6', '0', '0' )";
    }
    else
    {
        $query = "DELETE FROM SiteAwards WHERE User = '$usernameIn' AND AwardType = '6'";
    }

    s_mysql_query( $query );
}

function SetUserTrackedStatus( $usernameIn, $isUntracked )
{
    $query = "UPDATE UserAccounts SET Untracked = $isUntracked WHERE User = \"$usernameIn\"";
    s_mysql_query( $query );
}
