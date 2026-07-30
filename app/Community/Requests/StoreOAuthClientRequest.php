<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Models\OAuthClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOAuthClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'redirectUris' => ['required', 'array', 'min:1', 'max:5'],
            'redirectUris.*' => ['required', 'string', 'max:2048'],
            'type' => ['required', Rule::in(['confidential', 'public'])],
            'enableDeviceFlow' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ((array) $this->input('redirectUris', []) as $index => $redirectUri) {
                    if (!$this->isAllowedRedirectUri((string) $redirectUri)) {
                        $validator
                            ->errors()
                            ->add("redirectUris.{$index}", 'Enter a secure redirect URI without wildcards or fragments.');
                    }
                }
            },
        ];
    }

    protected function isAllowedRedirectUri(string $redirectUri): bool
    {
        if (str_contains($redirectUri, '*') || parse_url($redirectUri, PHP_URL_FRAGMENT) !== null) {
            return false;
        }

        $scheme = strtolower((string) parse_url($redirectUri, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($redirectUri, PHP_URL_HOST));

        if ($scheme === 'https') {
            return $host !== '';
        }

        if ($scheme === 'http') {
            return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        }

        $client = $this->route('client');

        // A registered client's stored type always wins over the request input,
        // because the client type is immutable after registration.
        $isPublicClient = $client instanceof OAuthClient
            ? !$client->confidential()
            : $this->input('type') === 'public';

        return
            $isPublicClient
            && preg_match('/^[a-z][a-z0-9+.-]*$/i', $scheme) === 1
            && !in_array($scheme, ['javascript', 'data', 'file'], true);
    }
}
