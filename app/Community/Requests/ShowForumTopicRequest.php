<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Models\ForumTopic;
use Illuminate\Foundation\Http\FormRequest;

class ShowForumTopicRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'comment' => 'nullable|integer|exists:forum_topic_comments,id',
            'page' => 'nullable|integer|min:1',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Always ensure `page` has a default value of 1
        // before validation runs.
        $this->merge([
            'page' => $this->input('page', 1),
        ]);
    }

    public function getCurrentPage(ForumTopic $topic, int $perPage): int
    {
        if ($this->validated('comment')) {
            $commentPosition = $topic->visibleComments()
                ->orderBy('created_at')
                ->where('id', '<', $this->validated('comment'))
                ->count();

            return (int) ceil(($commentPosition + 1) / $perPage);
        }

        return (int) $this->validated('page');
    }
}
