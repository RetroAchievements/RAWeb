<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use App\Models\LeaderboardEntry;
use Illuminate\Http\Request;

trait LeaderboardRequests
{
    /**
     * TODO
     *
     * @since 1.0
     */
    protected function lbinfoMethod(Request $request): array
    {
        /*
         * TODO
         */

        return [];
    }

    /**
     * TODO
     *
     * @since 1.0
     */
    protected function submitlbentryMethod(Request $request): array
    {
        $this->authorize('create', LeaderboardEntry::class);

        /*
         * TODO
         */

        // $lbID = seekPOSTorGET('i', 0, 'integer');
        // $score = seekPOSTorGET('s', 0, 'integer');
        // $validation = seekPOSTorGET('v'); // Ignore for now?
        // $response['Response'] = SubmitLeaderboardEntry($user, $lbID, $score, $validation);
        // $response['Success'] = $response['Response']['Success'];
        // if ($response['Success'] == false) {
        //     $response['Error'] = $response['Response']['Error'];
        // }

        return [];
    }
}
