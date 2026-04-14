<?php

declare(strict_types=1);

namespace S\Old;

final readonly class LineItem
{
    public function __construct(
        public int $priceCents,
        public int $qty,
    ) {}
}

final class OrderService
{
    /** @param list<LineItem> $items */
    public function checkout(int $userId, array $items): void
    {
        // бизнес-логика
        $total = 0.0;
        foreach ($items as $item) {
            $total += ($item->priceCents / 100) * $item->qty;
        }

        // инфраструктура: запись (псевдо)
        file_put_contents('/tmp/orders.log', json_encode([
                'userId' => $userId,
                'items' => $items,
                'total' => $total,
            ], JSON_THROW_ON_ERROR) . PHP_EOL, FILE_APPEND);

        // интеграция: email (псевдо)
        mail('user@example.com', 'Order placed', "Total: {$total}");
    }
}