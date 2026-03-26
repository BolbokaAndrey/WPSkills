<?php

class UserServiceBad
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Бизнес-логика
    public function register(array $data): void
    {
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }

        $this->save($data);
        $this->sendWelcomeEmail($data['email']);
        $this->log("User registered: {$data['email']}");
    }

    // Работа с БД — чужая ответственность
    private function save(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO users (email) VALUES (?)');
        $stmt->execute([$data['email']]);
    }

    // Отправка почты — чужая ответственность
    private function sendWelcomeEmail(string $email): void
    {
        mail($email, 'Welcome!', 'Thanks for signing up.');
    }

    // Логирование — чужая ответственность
    private function log(string $message): void
    {
        file_put_contents('app.log', date('Y-m-d') . " $message\n", FILE_APPEND);
    }
}


//---------------------------------------



class UserRepository
{
    public function __construct(private PDO $db) {}

    public function save(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO users (email) VALUES (?)');
        $stmt->execute([$data['email']]);
    }
}

class Mailer
{
    public function sendWelcome(string $email): void
    {
        mail($email, 'Welcome!', 'Thanks for signing up.');
    }
}

class Logger
{
    public function log(string $message): void
    {
        file_put_contents('app.log', date('Y-m-d') . " $message\n", FILE_APPEND);
    }
}

class UserService
{
    public function __construct(
        private UserRepository $repository,
        private Mailer         $mailer,
        private Logger         $logger,
    ) {}

    public function register(array $data): void
    {
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }

        $this->repository->save($data);
        $this->mailer->sendWelcome($data['email']);
        $this->logger->log("User registered: {$data['email']}");
    }
}