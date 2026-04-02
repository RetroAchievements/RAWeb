<?php

declare(strict_types=1);

namespace App\Api\V2\LookingForGroupPosts;

use App\Models\Game;
use App\Models\User;
use App\Community\Enums\LookingForGroupStatus;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class LookingForGroupPostRequest extends ResourceRequest
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
     * Rules for creating an LFG post.
     */
    protected function createRules(): array
    {
        return [
            'type' => ['required', 'string', 'in:looking-for-group-posts'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'maxPlayers' => ['nullable', 'integer', 'min:1', 'max:99'],
            'scheduledFor' => ['nullable', 'date', 'after:now'],
            'game' => ['required', 'array'],
            'game.type' => ['required', 'string', 'in:games'],
            'game.id' => [
                'required',
                'integer',
                'exists:games,id',
            ],
        ];
    }

    /**
     * Rules for updating an LFG post.
     */
    protected function updateRules(): array
    {
        return [
            'type' => ['required', 'string', 'in:looking-for-group-posts'],
            'title' => ['sometimes', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'maxPlayers' => ['sometimes', 'integer', 'min:1', 'max:99'],
            'scheduledFor' => ['sometimes', 'nullable', 'date'],
            'status' => [
                'sometimes',
                'string',
                'in:' . implode(',', array_column(LookingForGroupStatus::cases(), 'value')),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The resource type must be looking-for-group-posts.',
            'game.type.in' => 'The game type must be games.',
            'game.id.exists' => 'The specified game does not exist.',
            'maxPlayers.min' => 'Max players must be at least 1.',
            'maxPlayers.max' => 'Max players cannot exceed 99.',
            'scheduledFor.after' => 'Scheduled time must be in the future.',
            'status.in' => 'The status must be one of: ' . implode(', ', array_column(LookingForGroupStatus::cases(), 'value')),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'title',
            'note' => 'note',
            'maxPlayers' => 'max players',
            'scheduledFor' => 'scheduled for',
            'game' => 'game',
            'game.type' => 'game type',
            'game.id' => 'game id',
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
        // Additional create validation if needed
    }

    /**
     * Additional validation for update requests.
     */
    protected function validateUpdateRules($validator, array $data): void
    {
        $post = $this->model();
        $user = $this->user();

        if (!$post || !$user) {
            return;
        }

        // Validate status transitions - only check if status is being changed
        if (isset($data['status'])) {
            // Handle both string and enum values from merged data
            $statusValue = is_string($data['status']) ? $data['status'] : $data['status']->value;

            // Only validate if the status is actually changing
            if ($statusValue !== $post->status->value) {
                $newStatus = LookingForGroupStatus::tryFrom($statusValue);

                if (!$newStatus) {
                    $validator->errors()->add('status', 'Invalid status value.');
                    return;
                }

                if (!$post->status->canTransitionTo($newStatus)) {
                    $validator->errors()->add('status', 'Invalid status transition.');
                }

                // Only creator can change status
                if ($post->creator_user_id !== $user->id) {
                    $validator->errors()->add('status', 'Only the creator can change the post status.');
                }
            }
        }

        // Validate max players doesn't reduce below accepted players
        if (isset($data['maxPlayers'])) {
            $newMax = $data['maxPlayers'];
            $acceptedCount = $post->getAcceptedPlayersCount();

            if ($newMax < $acceptedCount) {
                $validator->errors()->add('maxPlayers', "Max players cannot be less than the number of accepted players ({$acceptedCount}).");
            }
        }
    }
}
