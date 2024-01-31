<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        /**
         * https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
         * medialibrary handles conversions of jpg, png, svg, pdf, mp4, mov or webm
         * TODO: video conversion requires PHP-FFmpeg which is not isntallable atm - check back in a while
         * TODO: check if dimensions rule works when ffprobe is available
         * video/mp4,video/webm,video/mpeg,video/quicktime
         * TODO: "gd-webp cannot allocate temporary buffer" when trying to upload a webp
         * image/webp
         * those two are the same:
         * 'mimes:png,jpeg,gif,svg',
         * 'mimetypes:image/png,image/jpeg,image/gif,image/svg,image/svg+xml',
         */
        $imageRules = [
            'max:2000',
            'mimes:png,jpeg,gif,svg',
        ];
        $maxDimension = 4500;

        if ($this->avatar && mb_strpos($this->avatar->getMimeType(), 'video') === false) {
            $imageRules[] = Rule::dimensions()->maxWidth($maxDimension)->maxHeight($maxDimension);
        }

        return [
            'avatar' => $imageRules,
            'country' => 'nullable|string',
            'motto' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:50',
            'locale_date' => 'nullable|string|max:50',
            // 'locale_time' => 'nullable|string|max:50',
            'locale_number' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
        ];
    }
}
