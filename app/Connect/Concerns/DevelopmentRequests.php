<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use App\Platform\Actions\LinkHashToGameAction;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\GameHashSet;
use App\Platform\Models\MemoryNote;
use Illuminate\Http\Request;

trait DevelopmentRequests
{
    protected function gameslistMethod(Request $request): array
    {
        $request->validate(
            [
                'c' => 'required|integer',
            ],
            $messages = [],
            $attributes = [
                'c' => 'System ID',
            ]
        );

        $games = Game::where('system_id', $request->input('c'))
            ->without('system', 'media')
            ->get(['id', 'title'])
            ->mapWithKeys(fn (Game $game) => [$game->id => $game->title])
            ->toArray();

        // make sure an empty array will be an object in the response
        $games = (object) $games;

        return [
            'response' => $games,
        ];
    }

    protected function submitgametitleMethod(Request $request): array
    {
        $this->authorize('create', GameHash::class);

        $request->validate(
            [
                'g' => 'nullable|integer',
                'm' => 'required|string|size:32',
                'i' => 'required|string|min:2|max:250',
                'c' => 'required|integer|exists:systems,id',
                'd' => 'nullable|string',
            ],
            $messages = [],
            $attributes = [
                'g' => 'Game ID',
                'm' => 'Hash',
                'i' => 'Game Title',
                'c' => 'System ID',
                'd' => 'Hash Title',
            ]
        );

        $gameId = $request->input('g');
        $hash = $request->input('m');
        $gameTitle = $request->input('i');
        $gameHashTitle = $request->input('d');
        $systemId = $request->input('c');

        $game = null;

        if ($gameId) {
            $game = Game::find($gameId);
        }

        if (!$game) {
            $game = Game::where('system_id', $systemId)
                ->where('title', $gameTitle)
                ->first();
        }

        if (!$game) {
            $this->authorize('create', Game::class);

            $game = Game::create([
                'system_id' => $systemId,
                'title' => $gameTitle,
            ]);
        }

        /** @var Game $game */

        /** @var LinkHashToGameAction $linkHashToGameAction */
        $linkHashToGameAction = app()->make(LinkHashToGameAction::class);
        $linkHashToGameAction->execute($hash, $game, $gameHashTitle);

        return [
            'response' => [
                'md5' => $hash,
                'systemId' => $game->system_id,
                'gameId' => $game->id,
                'gameTitle' => $gameTitle,
            ],
        ];
    }

    /**
     * Used by RAIntegration
     *
     * @since 1.0
     */
    protected function codenotes2Method(Request $request): array
    {
        return $this->codenotesMethod($request);
    }

    /**
     * The original codenotes (not 2) call was deprecated in v1, used to return some text blob
     * Let's reuse it to get rid of that "2" eventually
     *
     * @since 1.0
     */
    protected function codenotesMethod(Request $request): array
    {
        $this->authorize('viewAny', MemoryNote::class);

        $request->validate(
            [
                'g' => 'required|integer',
            ],
            $messages = [],
            $attributes = [
                'g' => 'Game ID',
            ]
        );

        $gameId = $request->input('g');

        /** @var ?Game $game */
        $game = Game::find($gameId);
        abort_if($game === null, 404, 'Game with ID "' . $gameId . '" not found');

        /** @var ?GameHashSet $gameHashSet */
        $gameHashSet = $game->gameHashSets()->compatible()->first();
        abort_if($gameHashSet === null, 409, 'Game with ID "' . $gameId . '" has no hashes');

        $memoryNotes = $gameHashSet->memoryNotes()
            // ->whereNotNull('body')
            ->with('user')
            ->get()
            /*
             * Read this as if it were hex, or it won't make sense.
             * TODO: what are we doing here? explain "Seamless"
             */
            ->map(fn ($note) => [
                'address' => sprintf('0x%06x', $note->address),
                'note' => $note->body,
                'username' => $note->user->display_name,
            ]);

        return [
            'gameId' => $game->id,
            'gameHashSetId' => $gameHashSet->id,
            'memoryNotes' => $memoryNotes,
        ];
    }

    /**
     * Used by RAIntegration
     * Called from Memory Inspector Dialog
     *
     * @since 1.0
     */
    protected function submitcodenoteMethod(Request $request): array
    {
        // TODO: check ability depending on
        // - whether or not a note already exists
        // - user owns the note
        $this->authorize('create', MemoryNote::class);

        $request->validate(
            [
                'g' => 'required|integer',
                'm' => 'required|integer',
                'n' => 'nullable|string',
            ],
            $messages = [],
            $attributes = [
                'g' => 'Game ID',
                'm' => 'Memory Address',
                'n' => 'Note',
            ]
        );

        $gameId = $request->input('g');
        $memoryAddress = $request->input('m');

        /** @var ?Game $game */
        $game = Game::find($gameId);
        abort_if($game === null, 404, 'Game with ID "' . $gameId . '" not found');

        /** @var ?GameHashSet $gameHashSet */
        $gameHashSet = $game->gameHashSets()->compatible()->first();
        abort_if($gameHashSet === null, 404, 'Game with ID "' . $gameId . '" has no hash sets');

        // TODO: define what to do when note body was left empty -> delete existing of given user? which one comes next?

        MemoryNote::upsert(
            [
                'game_hash_set_id' => $gameHashSet->id,
                'address' => $memoryAddress,
                'user_id' => $request->user('connect-token')->id,
                'body' => $request->input('n'),
            ],
            ['game_hash_set_id', 'address', 'user_id'],
            ['body'],
        );

        /** @var MemoryNote $note */
        $note = MemoryNote::orderByDesc('updated_at')
            ->firstWhere([
                'game_hash_set_id' => $gameHashSet->id,
                'address' => $memoryAddress,
            ]);

        return [
            'gameId' => $game->id,
            'gameHashSetId' => $note->game_hash_set_id,
            'address' => $note->address,
            'note' => $note->body,
        ];
    }

    /**
     * Used by RAIntegration
     * Called on Achievement Editor open and before uploading a badge
     *
     * @since 1.0
     */
    protected function badgeiterMethod(Request $request): array
    {
        /*
         * TODO: find out why those are even needed
         */

        //     $response['FirstBadge'] = 80;
        //     $response['NextBadge'] = file_get_contents("./BadgeIter.txt");
        //     settype($response['NextBadge'], 'integer');

        return [
            'firstBadge' => 80,
            'nextBadge' => null,
        ];
    }

    /**
     * Used by RAIntegration
     * Called from Achievements dialog when promoting to unofficial
     *
     * @since 1.0
     */
    protected function uploadachievementMethod(Request $request): array
    {
        $this->authorize('create', Achievement::class);

        //     //	Needs completely redoing from the app side!
        //     $newTitle = $request->input('n');
        //     $newDesc = $request->input('d');
        //     $newPoints = $request->input('z', 0, 'integer');
        //     $newMemString = $request->input('m');
        //     $newFlag = $request->input('f', 0, 'integer');
        //     $newBadge = $request->input('b');
        //     $errorOut = "";
        //     $response['Success'] = UploadNewAchievement(
        //         $user,
        //         $gameID,
        //         $newTitle,
        //         $newDesc,
        //         ' ',
        //         ' ',
        //         ' ',
        //         $newPoints,
        //         $newMemString,
        //         $newFlag,
        //         $achievementID,
        //         $newBadge,
        //         $errorOut
        //     );
        //     $response['AchievementID'] = $achievementID;
        //     $response['Error'] = $errorOut;

        return [];
    }

    /**
     * TODO
     *
     * @since 1.0
     */
    public function uploadbadgeimageMethod(Request $request): array
    {
        return [];
        // $response = ['Success' => true];
        //
        // $requestType = request('r');
        // $user = request('u');
        // $token = request('t');
        //
        // $validLogin = false;
        //
        // if (isset($token)) {
        //     $validLogin = RA_ReadTokenCredentials(
        //         $user,
        //         $token,
        //         $points,
        //         $truePoints,
        //         $unreadMessageCount,
        //         $permissions
        //     );
        // }
        // // if( $validLogin == false )
        // // {
        // //     $validLogin = RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
        // // }
        //
        // // function UploadToS3($filenameDest, $rawFile)
        // // {
        // //     if (!app()->environment('production')) {
        // //         return;
        // //     }
        // //
        // //     $client = new S3Client([
        // //         'region' => config('filesystems.disks.s3.region'),
        // //         'version' => 'latest',
        // //     ]);
        // //
        // //     // Register the stream wrapper from a client object
        // //     //$client->registerStreamWrapper();
        // //     //$url = "s3://i.retroachievements.org/$filenameDest";
        // //
        // //     $result = $client->putObject([
        // //         'Bucket' => config('filesystems.disks.s3.bucket'),
        // //         'Key' => "$filenameDest",
        // //         'Body' => fopen($filenameDest, 'r+'),
        // //     ]);
        // //
        // //     //$ok = imagepng( $rawFile, $url );
        // //     if ($result) {
        // //         Log::warning("Successfully uploaded $filenameDest to S3!");
        // //     } else {
        // //         Log::warning("FAILED to upload $filenameDest to S3!");
        // //     }
        // // }
        //
        // //  Infer from app
        // if (isset($_FILES["file"]) && isset($_FILES["file"]["name"])) {
        //     $requestType = mb_substr($_FILES["file"]["name"], 0, -4);
        //     Log::warning("RT: " . $requestType);
        // }
        // //Log::warning( "doupload.php" );
        // //Log::warning( print_r( $_FILES, true ) );
        // //	Interrogate requirements:
        // switch ($requestType) {
        //     case "uploadbadgeimage":
        //         $response['Response'] = $this->UploadBadgeImage($_FILES["file"]);
        //         break;
        //
        //     default:
        //         $this->DoRequestError("Unknown Request: '" . $requestType . "'");
        //         break;
        // }
        //
        // settype($response['Success'], 'boolean');
        // echo json_encode($response);
    }

    // private function UploadBadgeImage($file)
    // {
    //     Log::warning("UploadBadgeImage");
    //
    //     $response = [];
    //
    //     $filename = $file["name"];
    //     $filesize = $file["size"];
    //     $fileerror = $file["error"];
    //     $fileTempName = $file["tmp_name"];
    //
    //     $response['Filename'] = $filename;
    //     $response['Size'] = $filesize;
    //
    //     $allowedExts = ["png", "jpeg", "jpg", "gif"];
    //     $filenameParts = explode(".", $filename);
    //     $extension = mb_strtolower(end($filenameParts));
    //
    //     if ($filesize > 1048576) {
    //         $response['Error'] = "Error: image too big ($filesize)! Must be smaller than 1mb!";
    //     } else {
    //         if (!in_array($extension, $allowedExts)) {
    //             $response['Error'] = "Error: image type ($extension) not supported! Supported types: .png, .jpg, .jpeg, .gif";
    //         } else {
    //             if ($fileerror) {
    //                 if ($fileerror == UPLOAD_ERR_INI_SIZE) {
    //                     $response['Error'] = "Error: file too big! Must be smaller than 1mb please.";
    //                 } else {
    //                     $response['Error'] = "Error: $fileerror<br>";
    //                 }
    //             } else {
    //                 $nextBadgeFilename = file_get_contents(storage_path('app/BadgeIter.txt'));
    //                 settype($nextBadgeFilename, "integer");
    //
    //                 //	Produce filenames
    //
    //                 $newBadgeFilenameFormatted = str_pad($nextBadgeFilename, 5, "0", STR_PAD_LEFT);
    //
    //                 $destBadgeFile = "Badge/" . "$newBadgeFilenameFormatted" . ".png";
    //                 $destBadgeFileLocked = "Badge/" . "$newBadgeFilenameFormatted" . "_lock.png";
    //                 //$destBadgeFileBig = "Badge/" . "$newBadgeFilenameFormatted" . "_big.png";
    //                 //$destBadgeFileSmall = "Badge/" . "$newBadgeFilenameFormatted" . "_small.png";
    //                 //$destBadgeFileLockedSmall = "Badge/" . "$newBadgeFilenameFormatted" . "_locksmall.png";
    //                 //	Fetch file and width/height
    //
    //                 if ($extension == 'png') {
    //                     $tempImage = imagecreatefrompng($fileTempName);
    //                 } else {
    //                     if ($extension == 'jpg' || $extension == 'jpeg') {
    //                         $tempImage = imagecreatefromjpeg($fileTempName);
    //                     } else {
    //                         if ($extension == 'gif') {
    //                             $tempImage = imagecreatefromgif($fileTempName);
    //                         }
    //                     }
    //                 }
    //
    //                 [$width, $height] = getimagesize($fileTempName);
    //
    //                 //	Create all images
    //                 $smallPx = 32;
    //                 $normalPx = 64;
    //                 $largePx = 128;
    //
    //                 //$newSmallImage 		 = imagecreatetruecolor($smallPx, $smallPx);
    //                 $newImage = imagecreatetruecolor($normalPx, $normalPx);
    //                 //$newLargeImage 		 = imagecreatetruecolor($largePx, $largePx);
    //                 //$newSmallImageLocked = imagecreatetruecolor($smallPx, $smallPx);
    //                 $newImageLocked = imagecreatetruecolor($normalPx, $normalPx);
    //
    //                 //	Copy source to dest for all imaegs
    //                 //imagecopyresampled($newSmallImage, 	$tempImage, 0, 0, 0, 0, $smallPx, $smallPx, $width, $height);
    //                 imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, $normalPx, $normalPx, $width, $height);
    //                 //imagecopyresampled($newLargeImage, 	$tempImage, 0, 0, 0, 0, $largePx, $largePx, $width, $height);
    //
    //                 imagecopyresampled(
    //                     $newImageLocked,
    //                     $tempImage,
    //                     0,
    //                     0,
    //                     0,
    //                     0,
    //                     $normalPx,
    //                     $normalPx,
    //                     $width,
    //                     $height
    //                 );
    //                 imagefilter($newImageLocked, IMG_FILTER_GRAYSCALE);
    //                 imagefilter($newImageLocked, IMG_FILTER_CONTRAST, 20);
    //                 imagefilter($newImageLocked, IMG_FILTER_GAUSSIAN_BLUR);
    //
    //                 //imagecopyresampled($newSmallImageLocked, $tempImage, 0, 0, 0, 0, $smallPx, $smallPx, $width, $height);
    //                 //imagefilter( $newSmallImageLocked, IMG_FILTER_GRAYSCALE );
    //                 //imagefilter( $newSmallImageLocked, IMG_FILTER_CONTRAST, 20 );
    //                 ////imagefilter( $newSmallImageLocked, IMG_FILTER_GAUSSIAN_BLUR );
    //
    //                 $success = //imagepng( $newLargeImage, $destBadgeFileBig ) &&
    //                     //imagepng( $newSmallImage, $destBadgeFileSmall ) &&
    //                     //imagepng( $newSmallImageLocked, $destBadgeFileLockedSmall ) &&
    //                     imagepng($newImage, $destBadgeFile) &&
    //                     imagepng($newImageLocked, $destBadgeFileLocked);
    //
    //                 if ($success == false) {
    //                     Log::warning("UploadUserPic.php failed: Issues copying from $tempFileRawImage to $destBadgeFile");
    //                     $response['Error'] = "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
    //                 } else {
    //                     UploadToS3($destBadgeFile, $newImage);
    //                     UploadToS3($destBadgeFileLocked, $newImageLocked);
    //
    //                     $newBadgeContent = str_pad($nextBadgeFilename, 5, "0", STR_PAD_LEFT);
    //                     //echo "OK:$newBadgeContent";
    //                     $response['BadgeIter'] = $newBadgeContent;
    //
    //                     //	Increment and save this new badge number for next time
    //                     $newBadgeContent = str_pad($nextBadgeFilename + 1, 5, "0", STR_PAD_LEFT);
    //                     file_put_contents(storage_path('app/BadgeIter.txt'), $newBadgeContent);
    //                 }
    //             }
    //         }
    //     }
    //
    //     $response['Success'] = !isset($response['Error']);
    //     return $response;
    // }

    /*
     * TODO
     *
     * @param Request $request
     *
     * @return array
     *
     * @since 1.0
     */
}
