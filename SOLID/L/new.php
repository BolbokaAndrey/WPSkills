<?php

declare(strict_types=1);

namespace L\New;

interface ShapeInterface
{
    public function area(): int;
}

final class Rectangle implements ShapeInterface
{
    public function __construct(private int $w, private int $h) {}
    public function setWidth(int $w): void { $this->w = $w; }
    public function setHeight(int $h): void { $this->h = $h; }
    public function area(): int { return $this->w * $this->h; }
}

final class Square implements ShapeInterface
{
    public function __construct(private int $side) {}
    public function setSide(int $side): void { $this->side = $side; }
    public function area(): int { return $this->side * $this->side; }
}

function resizeAndMeasure(Rectangle $r): int
{
    $r->setWidth(5);
    $r->setHeight(2);
    return $r->area();
}

function resizeAndMeasure2(Square $s): int
{
    $s->setSide(5);
    return $s->area();
}