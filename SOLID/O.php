<?php

class OrderProcessorBad
{
    public function applyDiscount(Order $order, string $customerType): void
    {
        if ($customerType === 'regular') {
            $order->price *= 0.95;
        } elseif ($customerType === 'vip') {
            $order->price *= 0.80;
        } elseif ($customerType === 'seasonal') {
            // Каждый новый тип скидки — правка этого класса
            $order->price *= 0.70;
        }
    }
}


//---------------------------------------


interface IDiscount
{
    public function apply(Order $order): void;
}

class RegularDiscount implements IDiscount
{
    public function apply(Order $order): void
    {
        $order->price *= 0.95;
    }
}

class VipDiscount implements IDiscount
{
    public function apply(Order $order): void
    {
        $order->price *= 0.80;
    }
}

// Новый тип — просто новый класс, OrderProcessor не трогаем
class SeasonalDiscount implements IDiscount
{
    public function apply(Order $order): void
    {
        $order->price *= 0.70;
    }
}

class OrderProcessor
{
    public function applyDiscount(Order $order, IDiscount $discount): void
    {
        $discount->apply($order);
    }
}