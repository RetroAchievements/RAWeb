<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use Illuminate\Http\Request;

trait TicketRequests
{
    /**
     * TODO
     *
     * @since 1.0
     */
    protected function submitticketMethod(Request $request): array
    {
        /*
         * TODO
         */

        // case "submitticket":
        //     $idCSV = $request->input('i');
        //     $problemType = $request->input('p');
        //     $comment = $request->input('n');
        //     $md5 = $request->input('m');
        //     $response['Response'] = SubmitNewTicketsJSON($user, $idCSV, $problemType, $comment, $md5);
        //     $response['Success'] = $response['Response']['Success']; //	Passthru
        //     if (isset($response['Response']['Error'])) {
        //         $response['Error'] = $response['Response']['Error'];
        //     }
        //     break;

        return [];
    }
}
