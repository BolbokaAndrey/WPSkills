<?php

declare(strict_types=1);

namespace App\Handler;

use App\Http\Factory\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15: финальный обработчик запроса (не middleware).
 * Получает запрос уже прошедший через весь стек middleware.
 */
class ApiHandler implements RequestHandlerInterface
{
    public function __construct(private readonly HttpFactory $factory) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user   = $request->getAttribute('user');
        $method = $request->getMethod();
        $path   = $request->getUri()->getPath();

        $data = [
            'message' => "Handled $method $path",
            'user'    => $user,
            'query'   => $request->getQueryParams(),
            'body'    => $request->getParsedBody(),
        ];

        return $this->factory->createResponse(200)
            ->withBody($this->factory->createStream(json_encode($data, JSON_PRETTY_PRINT)));
    }
}
