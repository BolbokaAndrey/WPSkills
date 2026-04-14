<?php

declare(strict_types=1);

namespace L\New;

interface Eatable
{
    public function eat(): void;
}

interface Workable
{
    public function work(): void;
}

final class HumanWorker implements Eatable, Workable
{
    public function work(): void {}
    public function eat(): void {}
}

final class RobotWorker implements Workable
{
    public function work(): void {}
}