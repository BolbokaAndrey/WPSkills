<?php

declare(strict_types=1);

namespace D\New;

use PDO;

final readonly class RegisterUserRequest
{
    public function __construct(public string $email) {}
}

interface UserRepositoryInterface
{
    public function save(string $email): void;
}

interface EmailSenderInterface
{
    public function send(string $email): void;
}

final class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    public function save(string $email): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO users(email) VALUES(:email)');
        $stmt->execute(['email' => $email]);
    }
}

final class GuzzleEmailSender implements EmailSenderInterface
{
    public function __construct(private readonly \GuzzleHttp\Client $client) {}

    public function send(string $email): void
    {
        $this->client->post('https://email-gateway/send', ['json' => ['email' => $email]]);
    }
}

final class UserController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailSenderInterface    $emailSender,
    ) {}

    public function register(RegisterUserRequest $request): void
    {
        $this->userRepository->save($request->email);
        $this->emailSender->send($request->email);
    }
}

//  'pgsql:host=localhost;dbname=app', 'user', 'pass'  значения из env, назначается в конфигах

