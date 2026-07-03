<?php

declare(strict_types=1);

namespace App\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase;

    private const REASON_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        string $body = '',
        string $reasonPhrase = '',
        string $protocol = '1.1'
    ) {
        $this->setStatus($statusCode, $reasonPhrase);
        $this->body = new Stream($body);
        $this->protocolVersion = $protocol;

        foreach ($headers as $name => $value) {
            $this->headerNames[strtolower($name)] = $name;
            $this->headers[$name] = is_array($value) ? $value : [$value];
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $clone = clone $this;
        $clone->setStatus($code, $reasonPhrase);
        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private function setStatus(int $code, string $reasonPhrase = ''): void
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException("Invalid HTTP status code: $code");
        }
        $this->statusCode   = $code;
        $this->reasonPhrase = $reasonPhrase !== ''
            ? $reasonPhrase
            : (self::REASON_PHRASES[$code] ?? '');
    }
}
