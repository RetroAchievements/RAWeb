<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use App\Models\Emulator;
use App\Models\IntegrationRelease;
use Illuminate\Http\Request;

trait BootstrapRequests
{
    /**
     * @api {post} / Integration Version
     * @apiGroup Integration
     * @apiName integration
     * @apiDescription Version negotiation to determine whether client should update RAIntegration.
     * @apiVersion 2.0.0
     * @apiPermission none
     * @apiSuccess {String} latestVersion       Latest stable version.
     * @apiSuccess {String} latestVersionUrl    Latest stable version download URL.
     * @apiSuccess {String} LatestVersionUrlX64 Latest stable version x64 download URL.
     * @apiSuccess {String} minimumVersion      Minimum stable version.
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "latestVersion": "0.76.2.1",
     *      "latestVersionUrl": "https://media.retroachievements.org/integration/[FILE]",
     *      "latestVersionUrlX64": "https://media.retroachievements.org/integration/[FILE]",
     *      "minimumVersion": "0.76.1.0",
     *  }
     */

    /**
     * TODO:
     *
     * @apiSuccess {String} betaVersion Current beta version.
     * @apiSuccess {String} betaVersionUrl Current beta version download URL.
     *      "betaVersion": "0.76.3.4",
     *      "betaVersionUrl": "https://media.retroachievements.org/integration/[FILE]",
     *      "betaVersionUrlX64": "https://media.retroachievements.org/integration/[FILE]",
     */
    public function latestintegrationMethod(Request $request): array
    {
        // $beta = IntegrationRelease::latest()->first();
        $stable = IntegrationRelease::stable()->latest()->first();
        $minimum = IntegrationRelease::stable()->minimum()->latest()->first();

        return [
            // 'betaVersion' => $beta ? $beta->version : 0,
            // 'betaVersionUrl' => $beta ? $beta->getFirstMediaUrl('build_x86') : null,
            // 'betaVersionUrlX64' => $beta ? $beta->getFirstMediaUrl('build_x86') : null,
            'latestVersion' => $stable ? $stable->version : 0,
            'latestVersionUrl' => $stable ? $stable->getFirstMediaUrl('build_x86') : null,
            'latestVersionUrlX64' => $stable ? $stable->getFirstMediaUrl('build_x64') : null,
            'minimumVersion' => $minimum ? $minimum->version : 0,
            // 'minimumVersionUrl' => $minimum ? asset($minimum->getFirstMediaUrl('build_x86')) : null,
        ];
    }

    /**
     * @api {post} / Client Version
     * @apiGroup Client
     * @apiName client
     * @apiDescription Get the versions of the emulator client.
     * @apiVersion 2.0.0
     * @apiPermission none
     * @apiParam (Method)       {String} method="latestclient"
     * @apiParam (Parameter)    {String} [e] Emulator Integration ID
     * @apiSuccess {String} latestVersion       Latest stable version of the emulator client.
     * @apiSuccess {String} latestVersionUrl    Latest stable version download URL of the emulator client.
     * @apiSuccess {String} latestVersionUrlX64 Latest stable version x64 download URL of the emulator client.
     * @apiSuccess {String} minimumVersion      Minimum stable version of the emulator client.
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "latestVersion": "0.15.1",
     *      "latestVersionUrl": "https://media.retroachievements.org/emulator/[FILE]",
     *      "latestVersionUrlX64": "https://media.retroachievements.org/emulator/[FILE]",
     *      "minimumVersion": "0.15.1",
     *  }
     */

    /**
     * Used by RAIntegration
     * Called right when emulator started
     *
     * @since 1.0
     */
    protected function latestclientMethod(Request $request): array
    {
        $request->validate(
            [
                'e' => 'required_without:c|int',
                'c' => 'required_without:e|int',
            ],
            $messages = [],
            $attributes = [
                'e' => 'Emulator ID',
                'c' => 'System ID',
            ]
        );

        $emulator = null;

        /**
         * can be 0
         */
        $emulatorId = $request->input('e');
        if ($emulatorId !== null) {
            $emulator = Emulator::with('latestRelease')
                ->where('integration_id', $emulatorId)
                ->first();
            abort_if($emulator === null, 404, 'Emulator with Integration ID "' . $emulatorId . '" not found');
        }

        $systemId = $request->input('c');
        if ($systemId) {
            abort(400, 'Lookup by Console ID has been deprecated');
        }

        abort_if($emulator === null, 404, 'Emulator not found');

        $stable = $emulator->latestRelease;
        $minimum = $emulator->releases()->stable()->minimum()->latest()->first();

        if (!$stable || !$stable->version) {
            abort(
                404,
                'Emulator "' . $emulator->name . '" (Integration ID "' . $emulatorId . '") does not have a current release'
            );
        }

        return [
            'minimumVersion' => $minimum ? $minimum->version : null,
            'latestVersion' => $emulator->latestRelease->version,
            'latestVersionUrl' => $emulator->latestRelease->getFirstMediaUrl('build_x86'),
            'latestVersionUrlX64' => $emulator->latestRelease->getFirstMediaUrl('build_x64'),
        ];
    }
}
