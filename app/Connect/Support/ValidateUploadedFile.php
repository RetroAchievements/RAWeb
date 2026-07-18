<?php

declare(strict_types=1);

namespace App\Connect\Support;

use Illuminate\Http\UploadedFile;

trait ValidateUploadedFile
{
    protected function validateFile(UploadedFile $file, ?array $supportedExtensions, int $maximumSize): ?array
    {
        if ($file->getError()) {
            if ($file->getError() === UPLOAD_ERR_INI_SIZE) {
                return $this->invalidParameter('File too large.');
            }

            return $this->invalidParameter($file->getErrorMessage());
        }

        if ($supportedExtensions) {
            $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            if (!in_array($extension, $supportedExtensions)) {
                return $this->invalidParameter('Invalid file type.');
            }
        }

        if ($file->getSize() > $maximumSize) {
            return $this->invalidParameter('File too large.');
        }

        return null;
    }
}
