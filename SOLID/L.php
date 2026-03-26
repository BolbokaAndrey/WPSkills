<?php

class BirdBad
{
    public function fly(): string
    {
        return 'Flying...';
    }
}

class PenguinBad extends BirdBad
{
    public function fly(): string
    {
        // Пингвин — птица, но летать не может.
        // Подстановка ломает логику.
        throw new \LogicException('Penguins cannot fly!');
    }
}

function makeBirdBadFly(Bird $bird): void
{
    // Упадёт с исключением, если передать Penguin
    echo $bird->fly();
}



//---------------------------------------



// Базовый контракт — только то, что справедливо для ВСЕХ птиц
abstract class Bird
{
    abstract public function eat(): string;
}

// Отдельный контракт для умеющих летать
interface Flyable
{
    public function fly(): string;
}

class Sparrow extends Bird implements Flyable
{
    public function eat(): string { return 'Eating seeds'; }
    public function fly(): string { return 'Flying high'; }
}

class Penguin extends Bird
{
    public function eat(): string { return 'Eating fish'; }
    public function swim(): string { return 'Swimming fast'; }
}

// Функция явно требует то, что умеет делать
function makeBirdFly(Flyable $bird): void
{
    echo $bird->fly();
}