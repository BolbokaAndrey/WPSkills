<?php

declare(strict_types=1);

namespace L\Old;

interface Worker
{
    public function work(): void;
    public function eat(): void;
}

final class HumanWorker implements Worker
{
    public function work(): void {}
    public function eat(): void {}
}

final class RobotWorker implements Worker
{
    public function work(): void {}
    public function eat(): void
    {
        throw new LogicException('Robots do not eat');
    }
}