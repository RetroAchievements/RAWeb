<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Enums\ImageUploadType;

/**
 * Applies an uploaded image to a data array field.
 * Handles all the edge cases like expired temp files automatically.
 */
class ApplyUploadedImageToDataAction
{
    /**
     * Process and apply an uploaded image to a data field.
     *
     * If the field is not set, it will be unset (preserving existing value).
     * If the image processes successfully, the field is updated with the new path.
     * If processing fails (ie: the temp file on disk expired), the field is unset (preserving the existing value).
     *
     * @param array $data the form data array (modified in place)
     * @param string $field the field name to process
     * @param ImageUploadType $uploadType the type of image being uploaded
     */
    public function execute(array &$data, string $field, ImageUploadType $uploadType): void
    {
        // If we don't have a value, bail.
        if (!isset($data[$field])) {
            unset($data[$field]);

            return;
        }

        $processedImage = (new ProcessUploadedImageAction())->execute(
            $data[$field],
            $uploadType
        );

        if ($processedImage !== null) {
            $data[$field] = $processedImage;
        } else {
            // We usually fall in here if the temp file was auto-deleted from disk.
            // Unset the value.
            unset($data[$field]);
        }
    }
}
