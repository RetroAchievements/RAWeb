<?php

declare(strict_types=1);

namespace App\Http;

use Exception;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
use Inertia\Ssr\HttpGateway;
use Inertia\Ssr\Response;
use Inertia\Ssr\SsrException;

class InertiaSsrGateway extends HttpGateway
{
    /**
     * Dispatch the Inertia page to the SSR engine via HTTP.
     *
     * @param array<string, mixed> $page
     */
    public function dispatch(array $page, ?Request $request = null): ?Response
    {
        if (!$this->ssrIsEnabled($request ?? request())) {
            return null;
        }

        $isHot = Vite::isRunningHot();

        if (!$isHot && $this->shouldEnsureBundleExists() && !$this->bundleExists()) {
            return null;
        }

        $attempts = $isHot
            ? $this->getHotUrlAttempts('/__inertia_ssr')
            : [[$this->getProductionUrl('/render'), null]];

        $failure = null;

        $connectTimeout = (float) config('inertia.ssr.connect_timeout', 1);
        $timeout = (float) config('inertia.ssr.timeout', 2);

        foreach ($attempts as [$url, $hostHeader]) {
            try {
                $response = Http::withHeaders($hostHeader ? ['Host' => $hostHeader] : [])
                    ->connectTimeout($connectTimeout)
                    ->timeout($timeout)
                    ->post($url, $page);

                if ($response->failed()) {
                    $failure = $response->json();

                    continue;
                }

                if (!$data = $response->json()) {
                    $failure = ['error' => 'Empty SSR response', 'type' => 'connection'];

                    continue;
                }

                return new Response(
                    implode("\n", $data['head'] ?? []),
                    $data['body'] ?? ''
                );
            } catch (Exception $e) {
                if ($e instanceof StrayRequestException || $e instanceof SsrException) {
                    throw $e;
                }

                $failure = [
                    'error' => $e->getMessage(),
                    'type' => 'connection',
                ];
            }
        }

        $this->handleSsrFailure($page, $failure);

        return null;
    }

    /**
     * @return list<array{0: string, 1: ?string}>
     */
    private function getHotUrlAttempts(string $path): array
    {
        $hotUrl = rtrim(file_get_contents(Vite::hotFile()));
        $attempts = [[$hotUrl . $path, null]];

        $parts = parse_url($hotUrl) ?: [];
        $host = $parts['host'] ?? null;

        if (in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)) {
            $scheme = $parts['scheme'] ?? 'http';
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            $basePath = $parts['path'] ?? '';

            $dockerUrl = "{$scheme}://host.docker.internal{$port}{$basePath}";
            $hostHeader = ($host === '::1' ? '[::1]' : $host) . $port;

            $attempts[] = [$dockerUrl . $path, $hostHeader];
        }

        return $attempts;
    }
}
