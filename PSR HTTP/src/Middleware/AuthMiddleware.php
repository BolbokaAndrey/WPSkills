<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Factory\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Проверяет Bearer-токен в заголовке Authorization.
 * При успехе добавляет атрибут 'user' в запрос и передаёт дальше.
 * При неудаче возвращает 401 не вызывая следующий обработчик.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private const VALID_TOKEN = 'secret-token-123';

    public function __construct(private readonly HttpFactory $factory) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->factory->createResponse(401)
                ->withBody($this->factory->createStream('{"error":"Unauthorized"}'))
                ->withHeader('Content-Type', 'application/json');
        }

        $token = substr($authHeader, 7);
        if ($token !== self::VALID_TOKEN) {
            return $this->factory->createResponse(403)
                ->withBody($this->factory->createStream('{"error":"Forbidden"}'))
                ->withHeader('Content-Type', 'application/json');
        }

        // Передаём управление дальше, добавив атрибут с пользователем
        $request = $request->withAttribute('user', ['id' => 42, 'name' => 'John']);

        return $handler->handle($request);
    }
}
