<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use App\Connect\Controllers\ConnectApiController;
use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait AuthRequests
{
    /**
     * @api {post} / Token Login
     * @apiGroup Auth
     * @apiName loginToken
     * @apiDescription Login with token.
     * @apiVersion 2.0.0
     * @apiPermission none
     * @apiParam (Method)       {String} method="login"
     * @apiParam (Parameter)    {String} token Token
     * @apiUse loginResponse
     */

    /**
     * @api {post} / Username/Password Login
     * @apiGroup Auth
     * @apiName loginPassword
     * @apiDescription Login with username and password.
     * @apiVersion 2.0.0
     * @apiPermission none
     * @apiParam (Method)       {String} method="login"
     * @apiParam (Parameter)    {String} username Username
     * @apiParam (Parameter)    {String} password Password
     * @apiUse loginResponse
     */

    /**
     * @apiDefine loginResponse
     * @apiSuccess {String} username Current username
     * @apiSuccess {String} permalink URL to be used when linking to profile, including hash ID to be consistent
     *     between username changes
     * @apiSuccess {String} token Token to be used in subsequent calls.
     * @apiSuccess {Integer} pointsTotal Points score.
     * @apiSuccess {Integer} unreadMessagesCount Unread messages count.
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "avatarUrl2xl":
     *     "https://media.retroachievements.org/user/avatar/[id:0,3]/[id]/[sha1]/My-Avatar-2xl.png",
     *      "displayName": "Username",
     *      "id" : "1247623",
     *      "permalink": "https://retroachievements.org/u/1247623",
     *      "pointsTotal": 123,
     *      "unreadMessagesCount": 7,
     *      "username": "username",
     *      "token": "12371264afarg",
     *  }
     */

    /**
     * Used by RAIntegration
     * Called right when emulator started and when login is triggered.
     * Manually log in user by token or username/password (hence unguarded route)
     * from https://gist.github.com/nasrulhazim/25948dbf1f3a7d378bb5fe0463b49578
     */
    protected function loginMethod(Request $request): array
    {
        $request->validate(
            [
                'u' => 'required:t|string',
                'p' => 'required_without:t|string',
                't' => 'required_without:p|string',
            ],
            $messages = [],
            $attributes = [
                'u' => 'Username',
                'p' => 'Password',
                't' => 'Connect Token',
            ],
        );

        $user = null;

        if ($request->has('t')) {
            /** @var ?User $user */
            $user = auth('connect-token')->user();
            if ($user) {
                $now = Carbon::now();
                if ($now > $user->connect_token_expires_at) {
                    // expired app tokens have to be rerolled by logging in with username
                    $user = null;
                } elseif (strtolower($user->username) !== strtolower($request->input('u'))) {
                    // mismatch between user and token, ignore
                    $user = null;
                } else {
                    // refresh token
                    $user->connect_token_expires_at = $now->addDays(ConnectApiController::TOKEN_EXPIRY_DAYS);
                    $user->save();

                    // NOTE: token authenticator does not raise a Login event
                }
            }
        }

        if ($request->has('u') && $request->has('p')) {
            /*
             * Explicitly use web guard for username/password login as default guard has been set to api-integration
             * Will trigger the framework's Login event if successful (if user was not logged in yet that is)
             * NOTE: username is explicitly lowercase in the database, so lowercase the input to find the match
             */
            if (auth('web')->attempt(['username' => strtolower($request->input('u')), 'password' => $request->input('p')])) {
                /** @var User $user */
                $user = auth('web')->user();
                /*
                 * if app token is expired -> set a new one at login
                 * otherwise just extend expiry
                 */
                if (!$user->connect_token || Carbon::now() > $user->connect_token_expires_at) {
                    $user->rollConnectToken();
                }
                /*
                 * refresh token
                 */
                $user->connect_token_expires_at = Carbon::now()->addDays(ConnectApiController::TOKEN_EXPIRY_DAYS);
                $user->save();
            }
        }

        abort_if($user === null, 401);

        $user->loadCount([
            'unreadMessages',
        ]);

        $response = [
            'displayName' => $user->display_name,
            'pointsTotal' => $user->points_total,
            'unreadMessagesCount' => $user->unread_messages_count,
            'username' => $user->username,
            'token' => $user->connect_token,
        ];

        if ($this->acceptVersion > 1) {
            $response = array_merge($response, [
                'avatarMdUrl' => asset($user->avatar_md_url),
                'avatar2xlUrl' => asset($user->avatar_2xl_url),
                'id' => (string) $user->hash_id,
                'permalink' => $user->permalink,
            ]);
            ksort($response);
        }

        return $response;
    }
}
