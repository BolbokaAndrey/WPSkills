<?php

declare(strict_types=1);

final class DemoApplication
{
    private string $bootSignature;

    public function __construct()
    {
        // Имитация загрузки конфигурации и создания сервисов фреймворка.
        // В worker mode этот код выполняется один раз на воркер.
        $services = [];
        $seed = 'frankenphp-practice-bootstrap';

        for ($index = 0; $index < 10000; $index++) {
            $services[] = hash('sha256', $seed . $index);
        }

        $this->bootSignature = hash('sha256', implode('', $services));
    }

    public function handle(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'application' => 'frankenphp-practice',
            'status' => 'ok',
            'boot_signature' => substr($this->bootSignature, 0, 12),
        ]);
    }
}
