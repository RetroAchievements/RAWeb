<?php

use App\Legacy\Redirector\LegacyRedirector;
use Symfony\Component\HttpFoundation\Response;

return [
    /*
     * This is the class responsible for providing the URLs which must be redirected.
     * The only requirement for the redirector is that it needs to implement the
     * `Spatie\MissingPageRedirector\Redirector\Redirector`-interface
     */
    'redirector' => LegacyRedirector::class,

    /*
     * By default the package will only redirect 404s. If you want to redirect on other
     * response codes, just add them to the array. Leave the array empty to redirect
     * always no matter what the response code.
     */
    'redirect_status_codes' => [
        Response::HTTP_NOT_FOUND,
    ],

    /*
     * When using the `ConfigurationRedirector` you can specify the redirects in this array.
     * You can use Laravel's route parameters here.
     */
    'redirects' => [
        /*
         * lowercase routes
         */
        '/Achievement/{achievement}' => '/achievement/{achievement}',
        '/Game/{game}' => '/game/{game}',
        '/User/{user}' => '/user/{user}',

        /*
         * static pages
         */
        '/index.php' => '/',
        '/APIDemo.php' => '/docs',
        '/RA_API.php' => '/docs',
        '/download.php' => '/downloads',
        '/searchresults.php' => '/search',

        /*
         * auth
         */
        '/login.php' => '/login',
        '/createaccount.php' => '/register',
        '/resetPassword.php' => '/password/reset',

        /*
         * achievements
         */
        '/achievementInfo.php' => '/achievement/{ID}',
        '/achievementinspector.php' => '/game/{g}/achievements',
        '/achievementList.php' => '/achievements',
        '/awardedList.php' => '/achievements',

        /*
         * achievement tickets
         */
        '/reportissue.php' => '/achievement/{i}/tickets/create',
        '/ticketmanager.php' => '/tickets',

        /*
         * games
         */
        '/gameInfo.php' => '/game/{ID}',
        '/gameList.php' => '/system/{c}/games',
        '/gameSearch.php' => '/games',
        '/codenotes.php' => '/game/{g}/notes',
        '/linkedhashes.php' => '/game/{g}/assets',
        '/popularGames.php' => '/games/popular',

        /*
         * leaderboards
         */
        '/leaderboardinfo.php' => '/leaderboard/{i}',
        '/leaderboardList.php' => '/game/{g}/leaderboards',

        /*
         * user
         */
        '/userInfo.php' => '/user/{ID}',
        '/userList.php' => '/users',
        '/history.php' => '/user/{u}/history',
        '/historyexamine.php' => '/user/{u}/history/{d}',

        /*
         * user context
         */
        '/controlpanel.php' => '/settings',
        '/friends.php' => '/friends',
        '/createmessage.php' => '/message/create',
        '/inbox.php' => '/messages',
        '/gamecompare.php' => '/user/{f}/game/{ID}/compare',
        '/manageuserpic.php' => '/settings',

        /*
         * forums
         */
        '/forum.php' => '/forums/category/{c}',
        '/forumposthistory.php' => '/forums/posts',
        '/viewforum.php' => '/forums/forum/{f}',
        '/viewtopic.php' => '/forums/topic/{t}',
        '/forum/viewtopic.php' => '/forums/topic/{t}',

        '/createtopic.php' => '/forums/forum/{f}/topic/create',
        '/editpost.php' => '/forums/post/{c}/edit',

        /*
         * redirects (external)
         */
        '/faq.php' => 'https://docs.retroachievements.org/FAQ/',
        '/GetRA_API.php' => 'https://github.com/retroachievements/web-api-client-php',
        '/wiki-edit-redirect.php' => '/wiki-edit-redirect?page={page}',

        /*
         * rss
         */
        '/rss.php' => '/rss',
        '/rss-activity' => '/rss/activity',
        '/rss-activity.xml.php' => '/rss/activity',
        '/rss-forum' => '/rss/forum',
        '/rss-forum.xml.php' => '/rss/forum',
        '/rss-newachievements' => '/rss/achievements',
        '/rss-newachievements.xml.php' => '/rss/achievements',
        '/rss-news.xml.php' => '/rss/news',

    /*
     * TODO: external service routes
     */
        // '/BingSiteAuth.xml' => '',
        // '/channel.php' => '',

    /*
     * discarded & deprecated routes
     * redirecting those doesn't make sense because:
     * - api & form actions post data cannot/should not be forwarded
     * - route is not applicable anymore
     */
        // '/admin.php' => '',
        // '/attemptmerge.php' => '',
        // '/attemptrename.php' => '',
        // '/attemptunlink.php' => '',
        // '/developerstats.php' => '',
        // '/dorequest.php' => '', // RPC API route
        // '/doupload.php' => '', // RPC API route
        // '/echo_client.php' => '',
        // '/echo_client2.php' => '',
        // '/echo.php' => '',
        // '/emergencyuploadbadge.php' => '',
        // '/generategameforumtopic.php' => '',
        // '/login_app.php' => '', // RPC API route
        // '/logout.php' => '', // post route

        // '/BadgeIter.txt' => '',
        // '/ImageIter.txt' => '',
        // '/NewsIter.txt' => '',
        // '/LatestIntegration.html' => '', // RPC API route
        // '/LatestRAGensVersion.html' => '',
        // '/LatestRAMekaVersion.html' => '',
        // '/LatestRANESVersion.html' => '',
        // '/LatestRAP64Version.html' => '',
        // '/LatestRAPCEVersion.html' => '',
        // '/LatestRAPSXVersion.html' => '',
        // '/LatestRASnesVersion.html' => '',
        // '/LatestRAVBAVersion.html' => '',

        // '/feed.php' => '/',
        // '/ping_feed.php' => '',

        // '/largechat.php' => '',
        // '/ping_chat.php' => '',
        // '/ping.php' => '',
        // '/popoutchat.php' => '',
        // '/reorderSiteAwards.php' => '',

        // '/request.php' => '',
        // '/requestachievement.php' => '',
        // '/requestachievementinfo.php' => '',
        // '/requestaddfriend.php' => '',
        // '/requestallgametitles.php' => '',
        // '/requestallmyprogress.php' => '',
        // '/requestassociatefb.php' => '',
        // '/requestbadgenames.php' => '',
        // '/requestchangeemailaddress.php' => '',
        // '/requestchangefb.php' => '',
        // '/requestchangefriend.php' => '',
        // '/requestchangepassword.php' => '',
        // '/requestchangesiteprefs.php' => '',
        // '/requestcodenotes.php' => '',
        // '/requestcreatenewlb.php' => '',
        // '/requestcreateuser.php' => '',
        // '/requestcurrentlyactiveplayers.php' => '',
        // '/requestcurrentlyonlinelist.php' => '',
        // '/requestdeletelb.php' => '',
        // '/requestdeletemessage.php' => '',
        // '/requestfetchmessage.php' => '',
        // '/requestfriendlist.php' => '',
        // '/requestgameid.php' => '',
        // '/requestgametitles.php' => '',
        // '/requesthashlibrary.php' => '',
        // '/requestlbinfo.php' => '',
        // '/requestlogin.php' => '',
        // '/requestmessageids.php' => '',
        // '/requestmodifygame.php' => '',
        // '/requestmodifynews.php' => '',
        // '/requestmodifytopic.php' => '',
        // '/requestnewpic.php' => '',
        // '/requestnews.php' => '',
        // '/requestpatch.php' => '',
        // '/requestpatreonupdate.php' => '',
        // '/requestpostactivity.php' => '',
        // '/requestpostcomment.php' => '',
        // '/requestreconstructsiteawards.php' => '',
        // '/requestremovefb.php' => '',
        // '/requestresendactivationemail.php' => '',
        // '/requestresetachievements.php' => '',
        // '/requestresetlb.php' => '',
        // '/requestresetpassword.php' => '',
        // '/requestrichpresence.php' => '',
        // '/requestscore.php' => '',
        // '/requestscorerecalculation.php' => '',
        // '/requestsearch.php' => '',
        // '/requestsendmessage.php' => '',
        // '/requestsetmessageread.php' => '',
        // '/requestsubmitalt.php' => '',
        // '/requestsubmitcodenote.php' => '',
        // '/requestsubmiteditpost.php' => '',
        // '/requestsubmitforumtopic.php' => '',
        // '/requestsubmitgametitle.php' => '',
        // '/requestsubmitlbentry.php' => '',
        // '/requestsubmitticket.php' => '',
        // '/requestsubmittopiccomment.php' => '',
        // '/requestsubmitusermotto.php' => '',
        // '/requestsubmituserprefs.php' => '',
        // '/requestsubmitvid.php' => '',
        // '/requestsubmitwebticket.php' => '',
        // '/requestunlocks.php' => '',
        // '/requestunlockssite.php' => '',
        // '/requestupdateachievement.php' => '',
        // '/requestupdatelb.php' => '',
        // '/requestupdatesiteaward.php' => '',
        // '/requestupdateticket.php' => '',
        // '/requestupdateuser.php' => '',
        // '/requestuploadachievement.php' => '',
        // '/requestuploadbadge.php' => '',
        // '/requestuserplayedgames.php' => '',
        // '/requestvote.php' => '',
        // '/submitgamedata.php' => '',
        // '/submitlbdata.php' => '',
        // '/submitnews.php' => '',
        // '/submitvidurl.php' => '',

        // '/test.php' => '',
        // '/uploadpic.php' => '',
        // '/uploadpicinline.php' => '',
        // '/validateEmail.php' => '',
    ],

];
