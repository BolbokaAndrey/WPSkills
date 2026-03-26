<?php

class MySQLConnectionBad
{
    public function query(string $sql): array
    {
        // Конкретная реализация MySQL
        return [];
    }
}

class UserRepositoryBad
{
    // Жёстко привязан к MySQL — не заменить без правки класса
    private MySQLConnectionBad $db;

    public function __construct()
    {
        $this->db = new MySQLConnectionBad(); // зависимость создаётся внутри
    }

    public function findById(int $id): array
    {
        return $this->db->query("SELECT * FROM users WHERE id = $id");
    }
}



//---------------------------------------



// Абстракция — оба модуля зависят от неё
interface DatabaseConnection
{
    public function query(string $sql): array;
}

// Детали зависят от абстракции
class MySQLConnection implements DatabaseConnection
{
    public function query(string $sql): array
    {
        // MySQL-реализация
        return [];
    }
}

class PostgreSQLConnection implements DatabaseConnection
{
    public function query(string $sql): array
    {
        // PostgreSQL-реализация
        return [];
    }
}

// Модуль верхнего уровня зависит только от интерфейса
class UserRepository
{
    public function __construct(private DatabaseConnection $db) {}

    public function findById(int $id): array
    {
        return $this->db->query("SELECT * FROM users WHERE id = $id");
    }
}

// Подключение — снаружи, через DI-контейнер или вручную
$repository = new UserRepository(new MySQLConnection());
$repository = new UserRepository(new PostgreSQLConnection()); // легко заменить