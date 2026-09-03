<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
echo 'backend nginx: ' . ($_SERVER['HTTP_X_BACKEND'] ?? 'unknown') . PHP_EOL;
echo 'php-fpm: ' . gethostname() . PHP_EOL;
