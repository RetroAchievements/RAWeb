<?php

declare(strict_types=1);

namespace App\Api\V2\GameInvites;

use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\GameInviteStatus;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class GameInviteRequest extends ResourceRequest
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
     * Rules for creating a game invite.
     */
    protected function createRules(): array
    {
        return [
            'type' => ['required', 'string', 'in:game-invites'],
            'message' => ['nullable', 'string', 'max:500'],
            'game' => ['required', 'array'],
            'game.type' => ['required', 'string', 'in:games'],
            'game.id' => [
                'required',
                'integer',
                'exists:games,id',
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
     * Rules for updating a game invite.
     */
    protected function updateRules(): array
    {
        return [
            'type' => ['required', 'string', 'in:game-invites'],
            'status' => [
                'required',
                'string',
                'in:' . implode(',', array_column(GameInviteStatus::cases(), 'value')),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The resource type must be game-invites.',
            'game.type.in' => 'The game type must be games.',
            'game.id.exists' => 'The specified game does not exist.',
            'recipient.type.in' => 'The recipient type must be users.',
            'status.in' => 'The status must be one of: ' . implode(', ', array_column(GameInviteStatus::cases(), 'value')),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $method = $this->getMethod();

            if ($method === 'POST') {
                $this->validateCreateRules($validator);
            } elseif ($method === 'PATCH') {
                $this->validateUpdateRules($validator);
            }
        });
    }

    /**
     * Additional validation for create requests.
     */
    protected function validateCreateRules($validator): void
    {
        $sender = $this->user();
        $validated = $this->validated();

        // Prevent inviting yourself
        if (isset($validated['recipient']['id'])) {
            $recipientId = $validated['recipient']['id'];
            $recipient = User::where('ulid', $recipientId)
                ->orWhere('display_name', $recipientId)
                ->orWhere('username', $recipientId)
                ->first();

            if ($sender && $recipient && $sender->id === $recipient->id) {
                $validator->errors()->add('recipient.id', 'You cannot invite yourself to a game.');
            }
        }
    }

    /**
     * Additional validation for update requests.
     */
    protected function validateUpdateRules($validator): void
    {
        $invite = $this->model();
        $user = $this->user();
        $validated = $this->validated();

        if (!$invite || !$user) {
            return;
        }

        // Validate status transitions
        if (isset($validated['status'])) {
            $newStatus = GameInviteStatus::tryFrom($validated['status']);

            if (!$newStatus) {
                $validator->errors()->add('status', 'Invalid status value.');
                return;
            }

            if (!$invite->status->canTransitionTo($newStatus)) {
                $validator->errors()->add('status', 'Invalid status transition.');
            }

            // Check who can perform which transitions
            if ($newStatus === GameInviteStatus::Canceled && $invite->sender_user_id !== $user->id) {
                $validator->errors()->add('status', 'Only the sender can cancel an invite.');
            }

            if (in_array($newStatus, [GameInviteStatus::Accepted, GameInviteStatus::Declined]) && $invite->recipient_user_id !== $user->id) {
                $validator->errors()->add('status', 'Only the recipient can accept or decline an invite.');
            }
        }
    }
}
