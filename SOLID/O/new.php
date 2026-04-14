<?php

declare(strict_types=1);

namespace O\New;

interface DiscountInterface
{
    public function discount(int $totalCents): int;
}

final class DiscountNone implements DiscountInterface
{
    public function discount(int $totalCents): int
    {
        return 0;
    }
}

final class DiscountVip implements DiscountInterface
{
    public function discount(int $totalCents): int
    {
        return (int) round($totalCents * 0.10);
    }
}

final class DiscountCoupon implements DiscountInterface
{
    public function discount(int $totalCents): int
    {
        return 500;
    }
}

final class DiscountCalculator
{
    public function __construct(private DiscountInterface $discount) {}
    public function discount(int $totalCents): int
    {
        return $this->discount->discount($totalCents);
    }
}