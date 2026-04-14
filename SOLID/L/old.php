<?php

declare(strict_types=1);

namespace L\Old;

class Rectangle
{
    public function __construct(protected int $w, protected int $h) {}
    public function setWidth(int $w): void { $this->w = $w; }
    public function setHeight(int $h): void { $this->h = $h; }
    public function area(): int { return $this->w * $this->h; }
}

final class Square extends Rectangle
{
    public function setWidth(int $w): void
    {
        $this->w = $w;
        $this->h = $w;
    }

    public function setHeight(int $h): void
    {
        $this->w = $h;
        $this->h = $h;
    }
}

function resizeAndMeasure(Rectangle $r): int
{
    $r->setWidth(5);
    $r->setHeight(2);
    return $r->area(); // ожидаем 10
}