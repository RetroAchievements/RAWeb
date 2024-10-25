<?php

declare(strict_types=1);

namespace App\Community\Requests;

class ForumTopicCommentRequest extends StoreCommentRequest
{
    public function rules(): array
    {
        return [
            'body' => 'required|string|min:3|max:2000',
        ];
    }
}
