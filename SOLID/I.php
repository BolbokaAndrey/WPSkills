<?php

interface WorkerBad
{
    public function work(): void;
    public function eat(): void;
    public function sleep(): void;
}

class HumanWorkerBad implements WorkerBad
{
    public function work(): void  { echo 'Working'; }
    public function eat(): void   { echo 'Eating'; }
    public function sleep(): void { echo 'Sleeping'; }
}

// Робот вынужден реализовывать методы, которые ему не нужны
class RobotWorkerBad implements WorkerBad
{
    public function work(): void  { echo 'Working'; }
    public function eat(): void   { throw new \LogicException('Robots do not eat!'); }
    public function sleep(): void { throw new \LogicException('Robots do not sleep!'); }
}



//---------------------------------------



interface Workable
{
    public function work(): void;
}

interface Eatable
{
    public function eat(): void;
}

interface Sleepable
{
    public function sleep(): void;
}

// Человек реализует все три — ему они нужны
class HumanWorker implements Workable, Eatable, Sleepable
{
    public function work(): void  { echo 'Working'; }
    public function eat(): void   { echo 'Eating'; }
    public function sleep(): void { echo 'Sleeping'; }
}

// Робот реализует только то, что умеет
class RobotWorker implements Workable
{
    public function work(): void { echo 'Working'; }
}