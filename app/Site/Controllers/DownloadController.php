<?php

declare(strict_types=1);

namespace App\Site\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class DownloadController extends Controller
{
    public function index(): View
    {
        $emulators = getActiveEmulatorReleases();
        usort($emulators, function ($a, $b) {
            return strcasecmp($a['handle'], $b['handle']);
        });

        foreach ($emulators as &$emulator) {
            sort($emulator['systems']);
        }

        return view('download', ['emulators' => $emulators]);
    }
}
