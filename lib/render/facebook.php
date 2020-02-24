<?php

function RenderFBScript()
{
    $facebookAppId = getenv('FACEBOOK_CLIENT_ID');
    if (!$facebookAppId) {
        return;
    }
    $channelUrl = getenv('APP_URL') . '/channel.php';

    echo "<script>
        window.fbAsyncInit = function() {
          FB.init({
            appId      : $facebookAppId, // App ID
            channelUrl : '$channelUrl', // Channel File
            status     : true, // check login status
            cookie     : true, // enable cookies to allow the server to access the session
            xfbml      : true  // parse XFBML
          });
        };
        (function(d){
           var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
           if (d.getElementById(id)) {return;}
           js = d.createElement('script'); js.id = id; js.async = true;
           js.src = \"//connect.facebook.net/en_US/all.js\";
           ref.parentNode.insertBefore(js, ref);
         }(document));
    </script>\n";
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
        $fbConn = getFacebookConnection();

        if ($fbUser == 0) {
            //echo "req. associate!<br>";
            ////    Attempt associate?
            //$message = "/me/?access_token=$access_token";
            //$ret_obj = $fbConn->api($message,'GET');
            //if( $ret_obj )
            //{
            //echo "found 'me'!<br>";
            //$fbID = $ret_obj['id'];
            //if( $fbID !== 0 && $fbUser == 0 )
            //{
            //    error_log( __FUNCTION__ . " warning: inconsistency found $fbID $fbUser" );
            //    echo "inconsistency found $fbID $fbUser<br>";
            //    //    DB inconsistency: update our records!
            //    if( associateFB( $user, $fbID ) )
            //    {
            //        error_log( __FUNCTION__ . " warning: associated $user, $fbID" );
            //        echo "associate OK!<br>";
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
            // $message = "/$fbUser/?access_token=" . $fbConfig['appToken'];
            $message = "/$fbUser/?access_token=" . $fbConn->getAccessToken();
            //echo "<br>DEBUG:<br>" . $message . "<br>";
            $ret_obj = $fbConn->api($message, 'GET');
            if ($ret_obj) {
                $fbRealNameOut = $ret_obj['name'];
                $fbURLOut = $ret_obj['link'];
                //print_r( $ret_obj );
                return true;
            }
        }
    } catch (FacebookApiException $e) {
        // error_log("Facebook API Exception " . $e->getType());
        // error_log("Facebook API Exception Msg " . $e->getMessage());
        // error_log(__FUNCTION__ . " catch: input $fbUser");
        RenderFBLoginPrompt();
    }

    return false;
}
