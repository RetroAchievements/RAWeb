<?php

declare(strict_types=1);

namespace App\Api\V2\MessageThreads;

use App\Models\User;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class MessageThreadRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:message-threads'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'recipient' => ['required', 'array'],
            'recipient.type' => ['required', 'string', 'in:users'],
            'recipient.id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $user = User::where('ulid', $value)
                        ->orWhere('display_name', $value)
                        ->orWhere('username', $value)
                        ->first();

                    if (!$user) {
                        $fail('The specified recipient does not exist.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The resource type must be message-threads.',
            'recipient.type.in' => 'The recipient type must be users.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Prevent creating a message thread to yourself
            if (isset($this->validated()['recipient']['id'])) {
                $recipientId = $this->validated()['recipient']['id'];
                $sender = $this->user();

                if ($sender && ($sender->ulid === $recipientId || $sender->display_name === $recipientId || $sender->username === $recipientId)) {
                    $validator->errors()->add('recipient.id', 'You cannot send a message to yourself.');
                }
            }
        });
    }
}
