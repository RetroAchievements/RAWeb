<?php

declare(strict_types=1);

namespace App\Api\Internal\Requests;

class DemoteAchievementRequest extends BaseJsonApiRequest
{
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'data.type' => 'required|in:achievement-demotion',
            'data.attributes' => 'required|array',
            'data.attributes.achievementId' => 'required|integer|exists:Achievements,ID',
            'data.attributes.username' => 'required|string|exists:UserAccounts,User',
            'data.attributes.title' => 'nullable|string|max:64',
        ];
    }

    public function attributes(): array
    {
        // Set custom attribute names to prevent Laravel from converting camelCase.
        return [
            'data.attributes.achievementId' => 'data.attributes.achievementId',
            'data.attributes.username' => 'data.attributes.username',
            'data.attributes.title' => 'data.attributes.title',
        ];
    }

    public function messages(): array
    {
        return [
            'data.attributes.achievementId.exists' => 'The specified achievement does not exist.',
            'data.attributes.username.exists' => 'The specified username does not exist.',
        ];
    }

    public function getAchievementId(): int
    {
        return (int) $this->getAttribute('achievementId');
    }

    public function getUsername(): string
    {
        return $this->getAttribute('username');
    }

    public function getTitle(): ?string
    {
        return $this->getAttribute('title');
    }
}
