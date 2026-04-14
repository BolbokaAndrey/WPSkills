<?php

declare(strict_types=1);

namespace O\Old;

enum DiscountType: string
{
    case None = 'none';
    case Vip = 'vip';
    case Coupon = 'coupon';
}

final class DiscountCalculator
{
    public function discount(DiscountType $type, int $totalCents): int
    {
        return match ($type) {
            DiscountType::None => 0,
            DiscountType::Vip => (int) round($totalCents * 0.10),
            DiscountType::Coupon => 500,
        };
    }
}