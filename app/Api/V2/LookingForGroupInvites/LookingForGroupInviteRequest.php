<?php

declare(strict_types=1);

namespace App\Api\V2\LookingForGroupInvites;

use App\Models\LookingForGroupPost;
use App\Models\User;
use App\Community\Enums\LookingForGroupInviteStatus;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class LookingForGroupInviteRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        $method = $this->getMethod();

        if ($method === 'POST') {
            return $this->createRules();
        }

        if ($method === 'PATCH') {
            return $this->updateRules();
        }

        return [];
    }

    /**
     * Rules for creating an LFG invite.
     */
    protected function createRules(): array
    {
        return [
            'type' => ['required', 'string', 'in:looking-for-group-invites'],
            'message' => ['nullable', 'string', 'max:500'],
            'lookingForGroupPost' => ['required', 'array'],
            'lookingForGroupPost.type' => ['required', 'string', 'in:looking-for-group-posts'],
            'lookingForGroupPost.id' => [
                'required',
                'integer',
                'exists:looking_for_group_posts,id',
            ],
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
     * Rules for updating an LFG invite.
     */
    protected function updateRules(): array
    {
        return [
            'type' => ['required', 'string', 'in:looking-for-group-invites'],
            'status' => [
                'required',
                'string',
                'in:' . implode(',', array_column(LookingForGroupInviteStatus::cases(), 'value')),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The resource type must be looking-for-group-invites.',
            'lookingForGroupPost.type.in' => 'The LFG post type must be looking-for-group-posts.',
            'lookingForGroupPost.id.exists' => 'The specified LFG post does not exist.',
            'recipient.type.in' => 'The recipient type must be users.',
            'status.in' => 'The status must be one of: ' . implode(', ', array_column(LookingForGroupInviteStatus::cases(), 'value')),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'message' => 'message',
            'lookingForGroupPost' => 'looking for group post',
            'lookingForGroupPost.type' => 'looking for group post type',
            'lookingForGroupPost.id' => 'looking for group post id',
            'recipient' => 'recipient',
            'recipient.type' => 'recipient type',
            'recipient.id' => 'recipient id',
            'status' => 'status',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $validated = $validator->getData();
            $method = $this->getMethod();

            if ($method === 'POST') {
                $this->validateCreateRules($validator, $validated);
            } elseif ($method === 'PATCH') {
                $this->validateUpdateRules($validator, $validated);
            }
        });
    }

    /**
     * Additional validation for create requests.
     */
    protected function validateCreateRules($validator, array $data): void
    {
        $sender = $this->user();

        // Prevent inviting yourself
        if (isset($data['recipient']['id'])) {
            $recipientId = $data['recipient']['id'];
            $recipient = User::where('ulid', $recipientId)
                ->orWhere('display_name', $recipientId)
                ->orWhere('username', $recipientId)
                ->first();

            if ($sender && $recipient && $sender->id === $recipient->id) {
                $validator->errors()->add('recipient.id', 'You cannot invite yourself to an LFG post.');
            }
        }

        // Validate that the post can be joined
        if (isset($data['lookingForGroupPost']['id'])) {
            $postId = $data['lookingForGroupPost']['id'];
            $post = LookingForGroupPost::find($postId);

            if ($post && $sender && !$post->canBeJoinedBy($sender)) {
                $validator->errors()->add('lookingForGroupPost.id', 'You cannot join this LFG post.');
            }
        }
    }

    /**
     * Additional validation for update requests.
     */
    protected function validateUpdateRules($validator, array $data): void
    {
        $invite = $this->model();
        $user = $this->user();

        if (!$invite || !$user) {
            return;
        }

        // Validate status transitions - only check if status is being changed
        if (isset($data['status'])) {
            // Handle both string and enum values from merged data
            $statusValue = is_string($data['status']) ? $data['status'] : $data['status']->value;

            // Only validate if the status is actually changing
            if ($statusValue !== $invite->status->value) {
                $newStatus = LookingForGroupInviteStatus::tryFrom($statusValue);

                if (!$newStatus) {
                    $validator->errors()->add('status', 'Invalid status value.');
                    return;
                }

                if (!$invite->status->canTransitionTo($newStatus)) {
                    $validator->errors()->add('status', 'Invalid status transition.');
                }

                // Check who can perform which transitions
                if ($newStatus === LookingForGroupInviteStatus::Canceled && $invite->sender_user_id !== $user->id) {
                    $validator->errors()->add('status', 'Only the sender can cancel an invite.');
                }

                if (in_array($newStatus, [LookingForGroupInviteStatus::Accepted, LookingForGroupInviteStatus::Declined]) && $invite->recipient_user_id !== $user->id) {
                    $validator->errors()->add('status', 'Only the recipient can accept or decline an invite.');
                }
            }
        }
    }
}
