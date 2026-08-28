<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/DemoApplication.php';

$application = new DemoApplication();
$handleRequest = static function () use ($application): void {
    $application->handle();
};

if (function_exists('frankenphp_handle_request')) {
    while (frankenphp_handle_request($handleRequest)) {
        gc_collect_cycles();
    }

    return;
}

$handleRequest();
