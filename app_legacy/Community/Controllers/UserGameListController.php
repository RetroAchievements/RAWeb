<?php

declare(strict_types=1);

namespace LegacyApp\Community\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Carbon;
use Jenssegers\Optimus\Optimus;
use LegacyApp\Http\Controller;
use LegacyApp\Site\Models\User;

class UserGameListController extends Controller
{
    public static function getUserSetRequestsInformation(User $user): array
    {
        $requests = [];
        $requests['total'] = 0;
        $requests['pointsForNext'] = 0;
        $requests['maxSoftcoreReached'] = false;
        $points = 0;
        $maxSoftcoreThreshold = 10000; // Softcore points count towards requests up to 10000 points
    
        $points += $user->RAPoints + min($user->RASoftcorePoints, $maxSoftcoreThreshold);
        $requests['maxSoftcoreReached'] = ($user->RASoftcorePoints >= $maxSoftcoreThreshold);
    
        // logic behind the amount of requests based on player's score:
        $boundariesAndChunks = [
            100000 => 10000, // from 100k to infinite, +1 for each 10k chunk of points
            10000 => 5000,   // from 10k to 100k, +1 for each 5k chunk
            2500 => 2500,    // from 2.5k to 10k, +1 for each 2.5k chunk
            0 => 1250,       // from 0 to 2.5k, +1 for each 1.25k chunk
        ];
    
        $pointsLeft = $points;
        foreach ($boundariesAndChunks as $boundary => $chunk) {
            if ($pointsLeft >= $boundary) {
                $aboveBoundary = $pointsLeft - $boundary;
                $requests['total'] += floor($aboveBoundary / $chunk);
    
                if ($requests['pointsForNext'] === 0) {
                    $nextThreshold = $boundary + (floor($aboveBoundary / $chunk) + 1) * $chunk;
                    $requests['pointsForNext'] = $nextThreshold - $pointsLeft;
                }
    
                $pointsLeft = $boundary;
            }
        }
    
        // adding the number of years the user is here
        $requests['total'] += Carbon::now()->diffInYears($user->Created);

        settype($requests['total'], 'integer');
        settype($requests['pointsForNext'], 'integer');

        return $requests;
    }
}
