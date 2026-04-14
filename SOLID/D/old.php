<?php

declare(strict_types=1);

namespace D\Old;

final readonly class RegisterUserRequest
{
    public function __construct(public string $email) {}
}

final class UserController
{
    public function register(RegisterUserRequest $request): void
    {
        $pdo = new PDO('pgsql:host=localhost;dbname=app', 'user', 'pass'); // деталь
        $stmt = $pdo->prepare('INSERT INTO users(email) VALUES(:email)');
        $stmt->execute(['email' => $request->email]);

        $client = new \GuzzleHttp\Client(); // деталь
        $client->post('https://email-gateway/send', ['json' => ['email' => $request->email]]);
    }
}