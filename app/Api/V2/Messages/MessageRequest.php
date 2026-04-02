<?php

declare(strict_types=1);

namespace App\Api\V2\Messages;

use App\Models\User;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class MessageRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:messages'],
            'body' => ['required', 'string'],
            'messageThread' => ['required', 'array'],
            'messageThread.type' => ['required', 'string', 'in:message-threads'],
            'messageThread.id' => [
                'required',
                'string',
                'exists:message_threads,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The resource type must be messages.',
            'messageThread.type.in' => 'The message thread type must be message-threads.',
            'messageThread.id.exists' => 'The specified message thread does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $validated = $validator->getData();

            // Get thread ID from messageThread relationship
            $threadId = $validated['messageThread']['id'] ?? null;

            if ($threadId) {
                $user = $this->user();

                if ($user) {
                    // Check if user is a participant in the thread
                    $isParticipant = $user->messageThreads()
                        ->where('message_threads.id', $threadId)
                        ->whereNull('message_thread_participants.deleted_at')
                        ->exists();

                    if (!$isParticipant) {
                        $validator->errors()->add(
                            'messageThread.id',
                            'You can only reply to threads you are a participant in.'
                        );
                    }
                }
            }
        });
    }
}
