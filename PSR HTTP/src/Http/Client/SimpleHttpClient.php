<?php

declare(strict_types=1);

namespace App\Http\Client;

use App\Http\Factory\HttpFactory;
use App\Http\Client\Exception\NetworkException;
use App\Http\Client\Exception\RequestException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Простой пример PSR-18 клиента на стандартных средствах PHP.
 */
class SimpleHttpClient implements ClientInterface
{
    public function __construct(
        private readonly HttpFactory $factory,
        private readonly float $timeout = 10.0
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();

        if (!in_array($uri->getScheme(), ['http', 'https'], true) || $uri->getHost() === '') {
            throw new RequestException('Request must contain an HTTP URI with a host', $request);
        }

        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = "$name: $value";
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => $request->getMethod(),
                'header' => implode("\r\n", $headers),
                'content' => (string) $request->getBody(),
                'timeout' => $this->timeout,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);

        $body = @file_get_contents((string) $uri, false, $context);
        if ($body === false) {
            $error = error_get_last();
            throw new NetworkException($error['message'] ?? 'Network error', $request);
        }

        /** @var string[] $http_response_header */
        $responseHeaders = $http_response_header ?? [];
        $statusCode = $this->readStatusCode($responseHeaders);

        if ($statusCode === null) {
            throw new NetworkException('Server returned an invalid HTTP response', $request);
        }

        $response = $this->factory->createResponse($statusCode)
            ->withBody($this->factory->createStream($body));

        foreach ($responseHeaders as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $response = $response->withAddedHeader(trim($name), trim($value));
        }

        return $response;
    }

    /** @param string[] $headers */
    private function readStatusCode(array $headers): ?int
    {
        foreach ($headers as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }
}
