<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewForumPostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'usernames' => 'present|array',
            'usernames.*' => 'string',
            'ticketIds' => 'present|array',
            'ticketIds.*' => 'integer',
            'achievementIds' => 'present|array',
            'achievementIds.*' => 'integer',
            'gameIds' => 'present|array',
            'gameIds.*' => 'integer',
            'hubIds' => 'present|array',
            'hubIds.*' => 'integer',
        ];
    }
}
