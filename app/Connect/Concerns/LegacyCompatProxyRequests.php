<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use App\Models\IntegrationRelease;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

trait LegacyCompatProxyRequests
{
    /**
     * @api {get} https://retroachievements.org/LatestIntegration.html Integration Version
     * @apiGroup Integration
     * @apiName integration
     * @apiDescription Version negotiation to determine whether client should update RAIntegration.
     * @apiVersion 1.0.0
     * @apiPermission none
     * @apiSuccess {String} none Minimum stable version
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     0.76.1.0
     * TODO: @apiDeprecated since 2.0.0 (set sunset header)
     */

    /**
     * LatestIntegration.html proxy
     */
    public function legacyLatestIntegration(): string
    {
        $this->log('> GET LatestIntegration.html');

        $minimum = IntegrationRelease::stable()->minimum()->latest()->first();

        if (!$minimum) {
            return '0';
        }

        $version = $minimum->version;
        $this->log($version);

        return $version;
    }

    /**
     * @api {post} https://retroachievements.org/login_app.php Username/Password Login
     * @apiGroup Auth
     * @apiName loginPassword
     * @apiDescription Login with username and password.
     * @apiVersion 1.0.0
     * @apiPermission none
     * @apiUse loginPasswordV1
     * @apiUse loginResponseV1
     */

    /**
     * @api {post} https://retroachievements.org/login_app.php Token Login
     * @apiGroup Auth
     * @apiName loginToken
     * @apiDescription Login with token.
     * @apiVersion 1.0.0
     * @apiPermission none
     * @apiUse loginTokenV1
     * @apiUse loginResponseV1
     */

    /**
     * login_app.php proxy
     */
    public function legacyLogin(Request $request): Response
    {
        if ($request->isMethod('GET')) {
            $this->acceptVersion = 1;

            return $this->noop($request);
        }

        // forward to request, loginMethod
        $request->offsetSet('r', 'login');

        return $this->legacyRequest($request);
    }

    /**
     * @api {post} https://retroachievements.org/doupload.php Upload Badge
     * @apiGroup Development
     * @apiName uploadBadge
     * @apiDescription Upload achievement badge.
     * @apiVersion 1.0.0
     * @apiPermission none
     */

    /**
     * doupload.php proxy
     */
    public function legacyBadgeUploadRequest(Request $request): Response
    {
        $request->offsetSet('r', 'uploadbadgeimage');

        return $this->legacyRequest($request);
    }

    /**
     * @api {post} https://retroachievements.org/dorequest.php Integration Version
     * @apiGroup Integration
     * @apiName integration
     * @apiDescription Version negotiation to determine whether client should update RAIntegration.
     * @apiVersion 1.1.0
     * @apiPermission none
     * @apiSuccess {String} LatestVersion       Latest stable version
     * @apiSuccess {String} LatestVersionUrl    Latest stable version download URL
     * @apiSuccess {String} LatestVersionUrlX64 Latest stable version x64 download URL
     * @apiSuccess {String} MinimumVersion      Minimum stable version
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "LatestVersion": "0.76.2.1",
     *      "LatestVersionUrl": "https://static.retroachievements.org/media/integration/[FILE]",
     *      "LatestVersionUrlX64": "https://static.retroachievements.org/media/integration/[FILE]",
     *      "MinimumVersion": "0.76.1.0",
     *  }
     */

    /**
     * @api {post} https://retroachievements.org/dorequest.php Client Version
     * @apiGroup Client
     * @apiName client
     * @apiDescription Get the version of the emulator client.
     * @apiVersion 1.0.0
     * @apiPermission none
     * @apiParam (Method)       {String} r="latestclient"
     * @apiParam (Parameter)    {String} [e] Emulator Integration ID (preferred)
     * @apiParam (Parameter)    {String} [c] System/Console ID
     * @apiSuccess {String} LatestVersion Latest stable version of the emulator client
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "LatestVersion": "0.15.1",
     *  }
     */

    /**
     * @api {post} https://retroachievements.org/dorequest.php Client Version
     * @apiGroup Client
     * @apiName client
     * @apiDescription Get the versions of the emulator client.
     * @apiVersion 1.1.0
     * @apiPermission none
     * @apiParam (Method)       {String} r="latestclient"
     * @apiParam (Parameter)    {String} [e] Emulator Integration ID (preferred)
     * @apiParam (Parameter)    {String} [c] System/Console ID
     * @apiSuccess {String} LatestVersion       Latest stable version of the emulator client
     * @apiSuccess {String} LatestVersionUrl    Latest stable version download URL of the emulator client
     * @apiSuccess {String} LatestVersionUrlX64 Latest stable version x64 download URL of the emulator client
     * @apiSuccess {String} MinimumVersion      Minimum stable version of the emulator client
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "LatestVersion": "0.15.1",
     *      "LatestVersionUrl": "https://retroachievements.org/bin/[FILE]",
     *      "LatestVersionUrlX64": "https://retroachievements.org/bin/[FILE]",
     *      "MinimumVersion": "0.15.1",
     *  }
     */

    /**
     * @apiDefine loginPasswordV1
     * @apiParam (Method)       {String} r="login"
     * @apiParam (Parameter)    {String} u Username
     * @apiParam (Parameter)    {String} p Password
     */

    /**
     * @apiDefine loginTokenV1
     * @apiParam (Method)       {String} r="login"
     * @apiParam (Parameter)    {String} t Token
     */

    /**
     * @apiDefine loginResponseV1
     * @apiSuccess {String} User Username
     * @apiSuccess {String} Token Token to be used in subsequent calls
     * @apiSuccess {Integer} Score Points score
     * @apiSuccess {Integer} Messages Unread messages
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "DisplayName": "Username",
     *      "Messages": 7,
     *      "Score": 123,
     *      "Token": "abcdef0123456789",
     *      "User": "username",
     *  }
     */

    /**
     * @api {post} https://retroachievements.org/dorequest.php Username/Password Login
     * @apiGroup Auth
     * @apiName loginPassword
     * @apiDescription Login with username and password.
     * @apiVersion 1.1.0
     * @apiPermission none
     * @apiUse loginPasswordV1
     * @apiUse loginResponseV1
     */

    /**
     * @api {post} https://retroachievements.org/dorequest.php Token Login
     * @apiGroup Auth
     * @apiName loginToken
     * @apiDescription Login with token.
     * @apiVersion 1.1.0
     * @apiPermission none
     * @apiUse loginTokenV1
     * @apiUse loginResponseV1
     */

    /**
     * @api {post} https://retroachievements.org/dorequest.php Unlock
     * @apiGroup Achievement
     * @apiName achievementUnlock
     * @apiDescription Unlock an achievement.
     * @apiVersion 1.0.0
     * @apiPermission none
     * @apiParam (Method)       {String} r="awardachievement"
     * @apiParam (Parameter)    {String} a Achievement ID
     * @apiParam (Parameter)    {String} h Hardcore
     * @apiParam (Parameter)    {String} m Hash
     * @apiParam (Parameter)    {String} t Token
     * @apiParam (Parameter)    {String} u User
     * @apiSuccess {String} AchievementID Achievement ID
     * @apiSuccess {String} AchievementsRemaining Number of remaining locked achievements
     * @apiSuccess {String} Score Points score
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "AchievementID' => 1,
     *      "AchievementsRemaining' => 2,
     *      "Score' => 1000,
     *      "Success' => true,
     *  }
     */

    /*
     * TODO: Add remaining docs
     */

    /**
     * dorequest.php proxy
     */
    public function legacyRequest(Request $request): Response
    {
        $this->acceptVersion = 1;

        return $this->request($request);
    }
}
