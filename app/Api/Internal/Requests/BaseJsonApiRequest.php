<?php

declare(strict_types=1);

namespace App\Api\Internal\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use LaravelJsonApi\Core\Exceptions\JsonApiException;

abstract class BaseJsonApiRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt for JSON:API requests.
     * This matches the error structure used in Handler.php for JsonApiException.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw JsonApiException::error([
            'status' => '422',
            'code' => 'validation_error',
            'title' => 'The given data was invalid',
            'detail' => $validator->errors()->first(),
        ]);
    }

    /**
     * Get the JSON:API type from the request.
     */
    protected function getType(): ?string
    {
        return $this->input('data.type');
    }

    /**
     * Get a JSON:API attribute from the request.
     */
    protected function getAttribute(string $key): mixed
    {
        return $this->input("data.attributes.{$key}");
    }

    /**
     * Get a JSON:API relationship from the request.
     */
    protected function getRelationship(string $key): mixed
    {
        return $this->input("data.relationships.{$key}");
    }

    /**
     * Check if a JSON:API attribute exists in the request.
     */
    protected function hasAttribute(string $key): bool
    {
        return $this->has("data.attributes.{$key}");
    }

    /**
     * Check if a JSON:API relationship exists in the request.
     */
    protected function hasRelationship(string $key): bool
    {
        return $this->has("data.relationships.{$key}");
    }
}
