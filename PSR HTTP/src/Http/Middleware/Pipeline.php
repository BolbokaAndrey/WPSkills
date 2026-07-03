<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15: очередь middleware + финальный обработчик.
 *
 * Пайплайн сам реализует RequestHandlerInterface, поэтому его можно
 * вкладывать в другой пайплайн или вызывать напрямую.
 */
class Pipeline implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middleware = [];

    private ?RequestHandlerInterface $fallback = null;

    public function pipe(MiddlewareInterface $middleware): static
    {
        $clone = clone $this;
        $clone->middleware[] = $middleware;
        return $clone;
    }

    public function withFallback(RequestHandlerInterface $handler): static
    {
        $clone = clone $this;
        $clone->fallback = $handler;
        return $clone;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->middleware === []) {
            if ($this->fallback === null) {
                throw new LogicException('Pipeline has no middleware and no fallback handler');
            }
            return $this->fallback->handle($request);
        }

        // Снимаем первый middleware и создаём "хвост" как следующий обработчик
        $middleware = $this->middleware[0];
        $next       = clone $this;
        array_shift($next->middleware);

        return $middleware->process($request, $next);
    }
}
