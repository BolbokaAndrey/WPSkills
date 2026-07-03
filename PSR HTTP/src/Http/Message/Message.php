<?php

declare(strict_types=1);

namespace App\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class Message implements MessageInterface
{
    protected string $protocolVersion = '1.1';

    /** @var array<string, string[]> */
    protected array $headers = [];

    /** @var array<string, string> lowercase name => original name */
    protected array $headerNames = [];

    protected StreamInterface $body;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $lower = strtolower($name);
        if (!isset($this->headerNames[$lower])) {
            return [];
        }
        return $this->headers[$this->headerNames[$lower]];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        $value = $this->normalizeHeaderValue($value);
        $lower = strtolower($name);

        $clone = clone $this;

        if (isset($clone->headerNames[$lower])) {
            unset($clone->headers[$clone->headerNames[$lower]]);
        }

        $clone->headerNames[$lower] = $name;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $value = $this->normalizeHeaderValue($value);
        $lower = strtolower($name);

        $clone = clone $this;

        if (isset($clone->headerNames[$lower])) {
            $existing = $clone->headers[$clone->headerNames[$lower]];
            $clone->headers[$clone->headerNames[$lower]] = array_merge($existing, $value);
        } else {
            $clone->headerNames[$lower] = $name;
            $clone->headers[$name] = $value;
        }

        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $lower = strtolower($name);

        if (!isset($this->headerNames[$lower])) {
            return $this;
        }

        $clone = clone $this;
        $original = $clone->headerNames[$lower];
        unset($clone->headers[$original], $clone->headerNames[$lower]);

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /** @return string[] */
    private function normalizeHeaderValue(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                if (!is_string($v)) {
                    throw new InvalidArgumentException('Header values must be strings');
                }
            }
            return array_values($value);
        }
        throw new InvalidArgumentException('Header value must be a string or array of strings');
    }
}
