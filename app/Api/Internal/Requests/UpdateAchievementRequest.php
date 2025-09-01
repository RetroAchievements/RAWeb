<?php

declare(strict_types=1);

namespace App\Api\Internal\Requests;

class UpdateAchievementRequest extends BaseJsonApiRequest
{
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'data.type' => 'required|in:achievement',
            'data.id' => 'required|integer',
            'data.attributes' => 'nullable|array',
            'data.attributes.published' => 'nullable|boolean',
            'data.attributes.title' => 'nullable|string|max:64',
            'data.meta' => 'required|array',
            'data.meta.actingUser' => 'required|string|exists:UserAccounts,User',
        ];
    }

    public function attributes(): array
    {
        // Set custom attribute names to prevent Laravel from converting camelCase.
        return [
            'data.id' => 'data.id',
            'data.attributes.published' => 'data.attributes.published',
            'data.attributes.title' => 'data.attributes.title',
            'data.meta.actingUser' => 'data.meta.actingUser',
        ];
    }

    public function messages(): array
    {
        return [
            'data.id.required' => 'The achievement ID is required.',
            'data.meta.actingUser.exists' => 'The specified username does not exist.',
        ];
    }

    public function getActingUser(): string
    {
        return $this->input('data.meta.actingUser');
    }

    public function getTitle(): ?string
    {
        return $this->getAttribute('title');
    }

    public function getPublished(): ?bool
    {
        return $this->hasAttribute('published') ? (bool) $this->getAttribute('published') : null;
    }
}
