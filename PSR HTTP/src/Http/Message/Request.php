<?php

declare(strict_types=1);

namespace App\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{
    private string $method;
    private UriInterface $uri;
    private string $requestTarget = '';

    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        string $body = '',
        string $protocol = '1.1'
    ) {
        $this->method = strtoupper($method);
        $this->uri    = is_string($uri) ? new Uri($uri) : $uri;
        $this->body   = new Stream($body);
        $this->protocolVersion = $protocol;

        foreach ($headers as $name => $value) {
            $this->headerNames[strtolower($name)] = $name;
            $this->headers[$name] = is_array($value) ? $value : [$value];
        }

        // Автоматически добавляем Host из URI, если не задан
        if (!$this->hasHeader('Host') && $this->uri->getHost() !== '') {
            $host = $this->uri->getHost();
            if ($this->uri->getPort() !== null) {
                $host .= ':' . $this->uri->getPort();
            }
            $this->headerNames['host'] = 'Host';
            $this->headers['Host'] = [$host];
        }
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== '') {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }
        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->method = strtoupper($method);
        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $clone;
        }

        if ($uri->getHost() !== '') {
            $host = $uri->getHost();
            if ($uri->getPort() !== null) {
                $host .= ':' . $uri->getPort();
            }
            $clone->headerNames['host'] = 'Host';
            $clone->headers['Host'] = [$host];
        }

        return $clone;
    }
}
