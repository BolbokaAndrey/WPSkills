<?php

declare(strict_types=1);

namespace S\New;

final readonly class LineItem
{
    public function __construct(
        public int $priceCents,
        public int $qty,
    ) {}
}

final readonly class Order
{
    public function __construct(
        public int $userId,
        /** @var list<LineItem> */
        public array $items,
        public float $total,
    ) {}
}

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly Mailer          $mailer,
        private readonly OrderDomain      $orderDomain,
    ) {}

    /** @param list<LineItem> $items
     * @throws JsonException
     */
    public function checkout(int $userId, array $items): void
    {
        // бизнес-логика
        $total = $this->orderDomain->countTotal($items);

        // инфраструктура: запись (псевдо)
        $this->orderRepository->save(new Order($userId, $items, $total));

        // интеграция: email (псевдо)
        $this->mailer->send('user@example.com', 'Order placed', "Total: {$total}");
    }
}

final class OrderDomain
{
    public function countTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += ($item->priceCents / 100) * $item->qty;
        }

        return $total;
    }
}

final class OrderRepository
{
    /**
     * @throws JsonException
     */
    public function save(Order $order): void
    {
        file_put_contents('/tmp/orders.log', json_encode([
                'userId' => $order->userId,
                'items' => $order->items,
                'total' => $order->total,
            ], JSON_THROW_ON_ERROR) . PHP_EOL, FILE_APPEND);
    }
}

final class Mailer
{
    public function send(string $to, string $subject, string $body): void
    {
        mail($to, $subject, $body);
    }
}