<?php

declare(strict_types=1);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

for ($number = 1; $number <= 3; $number++) {
    echo "event: message\n";
    echo "data: event {$number}\n\n";

    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
    sleep(1);
}
