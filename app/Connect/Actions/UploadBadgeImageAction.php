<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\ValidateUploadedFile;
use App\Models\Achievement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class UploadBadgeImageAction extends BaseAuthenticatedApiAction
{
    use ValidateUploadedFile;

    protected UploadedFile $file;

    public function execute(User $user, UploadedFile $file): array
    {
        $this->user = $user;
        $this->file = $file;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->hasFile('file')) {
            return $this->missingParameters();
        }

        $this->file = $request->file('file');

        return null;
    }

    protected function process(): array
    {
        // Achievement badges don't have their own policy.
        // Assume that any user who can change the achievement badge can also upload new ones.
        if (!$this->user->can('updateField', [Achievement::class, null, 'image_name'])) {
            // The check for junior developers requires an Achievement instance so it can
            // ensure the user has a claim on the game. We don't know which Achievement this
            // badge will be associated to, so just make sure they have a claim on something.
            if (!$this->user->hasRole(Role::DEVELOPER_JUNIOR)) {
                return $this->mustBeDeveloper();
            }

            if (!$this->user->achievementSetClaims()->active()->exists()) {
                return $this->mustHaveActiveClaim();
            }
        }

        // Cap uploads to 1500/day per user.
        $rateLimitKey = 'badge-upload:' . $this->user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1500)) {
            return [
                'Success' => false,
                'Status' => 429,
                'Code' => 'too_many_requests',
                'Error' => 'Too many requests. Please try again later.',
            ];
        }
        RateLimiter::hit($rateLimitKey, 60 * 60 * 24);

        $error = $this->validateFile(
            $this->file,
            ['png', 'jpeg', 'jpg', 'gif'],
            1_048_576,
        );
        if ($error !== null) {
            return $error;
        }

        $sourceImage = match (strtolower(pathinfo($this->file->getClientOriginalName(), PATHINFO_EXTENSION))) {
            'png' => imagecreatefrompng($this->file->getPathname()),
            'jpg', 'jpeg' => imagecreatefromjpeg($this->file->getPathname()),
            'gif' => imagecreatefromgif($this->file->getPathname()),
            default => null,
        };
        if (!$sourceImage) {
            return $this->invalidParameter('Could not process image');
        }

        [$sourceWidth, $sourceHeight] = getimagesize($this->file->getPathname());

        $size = 64;
        $image = imagecreatetruecolor($size, $size);
        imagecopyresampled($image, $sourceImage, 0, 0, 0, 0, $size, $size, $sourceWidth, $sourceHeight);

        $imageLocked = imagecreatetruecolor($size, $size);
        imagecopyresampled($imageLocked, $sourceImage, 0, 0, 0, 0, $size, $size, $sourceWidth, $sourceHeight);
        imagefilter($imageLocked, IMG_FILTER_GRAYSCALE);
        imagefilter($imageLocked, IMG_FILTER_CONTRAST, 20);
        imagefilter($imageLocked, IMG_FILTER_GAUSSIAN_BLUR);

        $badgeRange = (new GetBadgeIdRangeAction())->execute();
        while (true) {
            $badgeIterator = str_pad((string) $badgeRange['NextBadge'], 5, '0', STR_PAD_LEFT);

            $imagePath = 'Badge/' . $badgeIterator . '.png';
            if (!Storage::disk('media')->exists($imagePath)) {
                $imagePathLocked = 'Badge/' . $badgeIterator . '_lock.png';

                $localImagePath = tempnam(sys_get_temp_dir(), $badgeIterator . '.png');
                $localImagePathLocked = tempnam(sys_get_temp_dir(), $badgeIterator . '_lock.png');
                break;
            }

            $badgeRange['NextBadge']++;
        }

        if (!imagepng($image, $localImagePath) || !imagepng($imageLocked, $localImagePathLocked)) {
            return $this->internalError('Failed to write image to disk.');
        }

        Storage::disk('media')->put($imagePath, file_get_contents($localImagePath));
        Storage::disk('media')->put($imagePathLocked, file_get_contents($localImagePathLocked));

        UploadToS3($localImagePath, $imagePath);
        UploadToS3($localImagePathLocked, $imagePathLocked);

        unlink($localImagePath);
        unlink($localImagePathLocked);

        return [
            'Success' => true,
            'Response' => [
                'BadgeIter' => $badgeIterator,
            ],
        ];
    }
}
