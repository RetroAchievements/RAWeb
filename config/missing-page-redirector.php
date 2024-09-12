<?php

use App\Support\Redirector\LegacyRedirector;

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
        Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND,
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
        '/reportissue.php' => '/achievement/{i}/report-issue',
        '/ticketmanager.php' => [
            'i' => '/ticket/{i}',
            'u' => '/user/{u}/tickets',
            'g' => '/game/{g}/tickets',
            'a' => '/achievement/{a}/tickets',
            'f' => '/tickets/most-reported-games',
            '' => '/tickets',
        ],

        /*
         * games
         */
        '/gameInfo.php' => '/game/{ID}',
        '/gameList.php' => '/system/{c}/games',
        '/gameSearch.php' => '/games',
        '/codenotes.php' => '/game/{g}/notes',
        '/linkedhashes.php' => '/game/{g}/hashes',
        '/popularGames.php' => '/games/popular',
        '/random.php' => '/game/random',

        /*
         * hashes
         */
        'latesthasheslinked.php' => '/manage/game-hashes',

        /*
         * leaderboards
         */
        '/leaderboardinfo.php' => '/leaderboard/{i}',

        /*
         * user
         */
        '/userInfo.php' => '/user/{ID}',
        '/userList.php' => '/users',
        '/history.php' => '/user/{u}/history',
        '/historyexamine.php' => '/user/{u}/history/{d}',
        '/usergameactivity.php' => '/user/{f}/game/{ID}',
        '/gamecompare.php' => '/user/{f}/game/{ID}/compare',

        /*
         * user context
         */
        '/controlpanel.php' => '/settings',
        '/friends.php' => '/friends',
        '/createmessage.php' => '/messages/create?to={t}&subject={s}&message={p}',
        // temp redirect
        '/message/create' => '/messages/create?to={to}&subject={subject}&message={message}',
        '/inbox.php' => '/messages',
        '/manageuserpic.php' => '/settings',

        /*
         * forums
         */
        '/forum.php' => '/forums/category/{c}', // TODO what if no category is specified?
        '/forumposthistory.php' => [
            'u' => '/user/{u}/posts',
            '' => '/forums/posts',
        ],
        '/viewforum.php' => '/forums/forum/{f}',
        '/viewtopic.php' => '/forums/topic/{t}',
        '/forum/viewtopic.php' => '/forums/topic/{t}',

        '/createtopic.php' => '/forums/forum/{f}/topic/create',
        '/editpost.php' => '/forums/post/{comment}/edit',

        /*
         * claims
         */
        '/claimlist.php' => [
            'u' => '/user/{u}/developer/claims',
            'g' => '/game/{g}/claims',
            '' => '/claims/active',
        ],
        '/expiringclaims.php' => '/claims/expiring',

        /*
         * redirects (external)
         */
        '/faq.php' => 'https://docs.retroachievements.org/general/faq.html',
        '/GetRA_API.php' => 'https://github.com/retroachievements/web-api-client-php',

        /*
         * rss
         */
        '/rss.php' => '/rss',

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
        // '/developerstats.php' => '',
        // '/dorequest.php' => '', // RPC API route
        // '/doupload.php' => '', // RPC API route
        // '/login_app.php' => '', // RPC API route
        // '/logout.php' => '', // post route

        // '/LatestIntegration.html' => '', // RPC API route

        // '/feed.php' => '/',
        // '/reorderSiteAwards.php' => '',
        // '/submitnews.php' => '',
        // '/validateEmail.php' => '',
    ],

];
