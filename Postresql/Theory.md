# PostgreSQL: развёртывание и основные отличия от MySQL

Примеры рассчитаны на PostgreSQL 16 и MySQL 8.0. Они показывают различия
диалектов SQL, а не утверждают, что одна СУБД всегда лучше другой.

## 1. Запуск PostgreSQL

Требования: Docker и Docker Compose v2.

```bash
# Запустить PostgreSQL в фоне
docker compose up -d

# Проверить состояние
docker compose ps

# Открыть psql внутри контейнера
docker compose exec postgres psql -U skill_user -d postgres_skill
```

Параметры подключения из внешнего клиента:

| Параметр | Значение |
|---|---|
| Host | `localhost` |
| Port | `5432` |
| Database | `postgres_skill` |
| User | `skill_user` |
| Password | `skill_password` |

Команды управления:

```bash
# Остановить контейнер, сохранив данные
docker compose down

# Удалить контейнер вместе с данными и заново выполнить init.sql
docker compose down -v
docker compose up -d
```

`init.sql` автоматически создаёт таблицы `users`, `orders`, индексы и тестовые
данные только при первом создании Docker volume.


## 2. Краткое сравнение

| Возможность | PostgreSQL 16 | MySQL 8.0 |
|---|---|---|
| Автонумерация | `IDENTITY`, также существует `serial` | `AUTO_INCREMENT` |
| Логический тип | Настоящий `boolean` | `BOOLEAN` — синоним `TINYINT(1)` |
| JSON | `json` и бинарный `jsonb` | Бинарное внутреннее хранение типа `JSON` |
| Массивы | Встроенный тип `type[]` | Отдельного типа массива нет, обычно используют JSON |
| Upsert | `ON CONFLICT` | `ON DUPLICATE KEY UPDATE` |
| Замена строки | Нет аналога `REPLACE INTO`; обычно `ON CONFLICT` или явный `DELETE` + `INSERT` | `REPLACE INTO` удаляет конфликтующую строку и вставляет новую |
| Возврат изменённых строк | `RETURNING` | Обычно отдельный `SELECT`, для id — `LAST_INSERT_ID()` |
| Регистронезависимый поиск | `ILIKE` | Зависит от collation; отдельного `ILIKE` нет |
| Кавычки идентификаторов | `"column"` | `` `column` ``; `"` работает в режиме `ANSI_QUOTES` |
| Частичные индексы | Поддерживаются через `WHERE` | Не поддерживаются напрямую |
| Полнота `GROUP BY` | Строгие правила SQL | Строго при включённом `ONLY_FULL_GROUP_BY` |
| Сортировка `NULL` | `NULLS FIRST` / `NULLS LAST`; по умолчанию `NULL` больше обычных значений | `NULL` сортируется как меньше обычных значений |
| Null-safe сравнение | `IS [NOT] DISTINCT FROM` | `<=>` |
| `UPDATE` с `JOIN` | `UPDATE ... SET ... FROM ...` | `UPDATE ... JOIN ... SET ...` |
| Пространства имён | База данных содержит схемы, используется `search_path` | `DATABASE` и `SCHEMA` практически синонимы, есть `USE db` |
| Ограничения | Есть deferrable-ограничения | Deferrable-ограничений нет |
| Автообновление timestamp | Обычно триггер | `ON UPDATE CURRENT_TIMESTAMP` |

Обе СУБД поддерживают ACID-транзакции, внешние ключи, оконные функции, CTE,
JSON, репликацию и планы запросов. В MySQL это сравнение предполагает движок
InnoDB.

## 3. Создание таблиц и автоинкремент

### PostgreSQL

```sql
CREATE TABLE products (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name text NOT NULL,
    price numeric(12, 2) NOT NULL CHECK (price >= 0),
    created_at timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

`IDENTITY` связан со стандартом SQL. `GENERATED ALWAYS` запрещает вручную
задавать id без явного `OVERRIDING SYSTEM VALUE`.

### MySQL

```sql
CREATE TABLE products (
    id bigint AUTO_INCREMENT PRIMARY KEY,
    name varchar(255) NOT NULL,
    price decimal(12, 2) NOT NULL CHECK (price >= 0),
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

## 4. Получение вставленной строки

PostgreSQL может сразу вернуть любые поля изменённой строки:

```sql
INSERT INTO users (email, full_name)
VALUES ('carol@example.com', 'Carol Sidorova')
RETURNING id, email, created_at;
```

В MySQL для автоинкрементного id обычно выполняют второй запрос:

```sql
INSERT INTO users (email, full_name)
VALUES ('carol@example.com', 'Carol Sidorova');

SELECT LAST_INSERT_ID();
```

PostgreSQL 16 поддерживает `RETURNING` также для `UPDATE` и `DELETE`.

```sql
UPDATE orders
SET status = 'paid'
WHERE id = 2
RETURNING id, status, amount;
```

## 5. Upsert и обработка конфликтов

### PostgreSQL

```sql
INSERT INTO users (email, full_name)
VALUES ('alice@example.com', 'Alice Updated')
ON CONFLICT (email) DO UPDATE
SET full_name = EXCLUDED.full_name
RETURNING id, email, full_name;
```

Игнорирование конфликта:

```sql
INSERT INTO users (email, full_name)
VALUES ('alice@example.com', 'Duplicate')
ON CONFLICT (email) DO NOTHING;
```

### MySQL

```sql
INSERT INTO users (email, full_name)
VALUES ('alice@example.com', 'Alice Updated') AS new
ON DUPLICATE KEY UPDATE full_name = new.full_name;
```

```sql
INSERT IGNORE INTO users (email, full_name)
VALUES ('alice@example.com', 'Duplicate');
```

В MySQL также есть `REPLACE INTO`, но это не обычный upsert: при конфликте
уникальности MySQL удаляет старую строку и вставляет новую. Это может сработать
иначе для внешних ключей, автоинкремента и триггеров. В PostgreSQL для
обновления обычно используют `ON CONFLICT DO UPDATE`, а для семантики
удаления-вставки пишут явные `DELETE` и `INSERT` в транзакции.

## 6. Строки, кавычки и регистр

Строки в обеих СУБД записываются в одинарных кавычках. Идентификаторы в
PostgreSQL заключаются в двойные кавычки:

```sql
SELECT "full_name" FROM users;
```

Без двойных кавычек PostgreSQL приводит идентификаторы к нижнему регистру.

Конкатенация:

```sql
-- PostgreSQL
SELECT full_name || ' <' || email || '>' AS label
FROM users;

-- MySQL
SELECT CONCAT(full_name, ' <', email, '>') AS label
FROM users;
```

Регистронезависимый поиск:

```sql
-- PostgreSQL
SELECT * FROM users WHERE full_name ILIKE '%alice%';

-- MySQL при регистронезависимой collation
SELECT * FROM users WHERE full_name LIKE '%alice%';
```

Результат `LIKE` в MySQL определяется collation столбца. Для переносимого
поведения можно явно выбрать collation или сравнивать нормализованные значения.

Важно отдельно проверить уникальность без учёта регистра. В PostgreSQL обычный
`UNIQUE (email)` различает `Alice@example.com` и `alice@example.com`, если не
использовать `citext` или функциональный индекс:

```sql
CREATE UNIQUE INDEX idx_users_email_lower ON users (lower(email));
```

В MySQL результат зависит от collation. Например, collation с суффиксом `_ci`
обычно делает сравнение регистронезависимым, а `_bin` или `_as_cs` — более
строгим.

## 7. LIMIT и OFFSET

Стандартная форма работает в обеих СУБД:

```sql
SELECT *
FROM orders
ORDER BY created_at DESC
LIMIT 10 OFFSET 20;
```

MySQL дополнительно разрешает `LIMIT offset, count`:

```sql
-- MySQL
SELECT * FROM orders ORDER BY created_at DESC LIMIT 20, 10;
```

В PostgreSQL такая форма не поддерживается.

## 8. Условные выражения и агрегация строк

```sql
-- PostgreSQL
SELECT id,
       CASE WHEN active THEN 'active' ELSE 'blocked' END AS state
FROM users;

-- MySQL: допустим CASE, также есть нестандартная функция IF
SELECT id, IF(active, 'active', 'blocked') AS state
FROM users;
```

```sql
-- PostgreSQL
SELECT u.id,
       string_agg(o.status, ', ' ORDER BY o.created_at) AS statuses
FROM users AS u
JOIN orders AS o ON o.user_id = u.id
GROUP BY u.id;

-- MySQL
SELECT u.id,
       GROUP_CONCAT(o.status ORDER BY o.created_at SEPARATOR ', ') AS statuses
FROM users AS u
JOIN orders AS o ON o.user_id = u.id
GROUP BY u.id;
```

У MySQL `GROUP_CONCAT` ограничен настройкой `group_concat_max_len`; при большом
результате строка может быть обрезана. В PostgreSQL у `string_agg` нет такого
малого лимита по умолчанию, но остаются общие ограничения на размер значения и
память запроса.

## 9. Дата и время

PostgreSQL поддерживает интервалы и явный тип `timestamptz`:

```sql
SELECT CURRENT_TIMESTAMP + INTERVAL '7 days';

SELECT date_trunc('month', created_at) AS month,
       count(*) AS order_count
FROM orders
GROUP BY date_trunc('month', created_at)
ORDER BY month;
```

Эквиваленты MySQL:

```sql
SELECT DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 7 DAY);

SELECT DATE_FORMAT(created_at, '%Y-%m-01') AS month,
       count(*) AS order_count
FROM orders
GROUP BY DATE_FORMAT(created_at, '%Y-%m-01')
ORDER BY month;
```

В PostgreSQL `timestamp with time zone` хранит момент времени и отображает его
в часовом поясе текущей сессии. Само имя исходного часового пояса не хранится.
В MySQL `TIMESTAMP` тоже зависит от часового пояса сессии, а `DATETIME` хранит
дату и время без такого преобразования.

## 10. JSON и JSONB

PostgreSQL:

```sql
-- Получить текстовое значение
SELECT email, profile ->> 'city' AS city
FROM users;

-- Найти JSONB, содержащий заданный фрагмент
SELECT *
FROM users
WHERE profile @> '{"city": "Moscow"}'::jsonb;

-- Обновить одно поле
UPDATE users
SET profile = jsonb_set(profile, '{age}', '31'::jsonb)
WHERE email = 'alice@example.com'
RETURNING profile;
```

Оператор `@>` может использовать GIN-индекс.

MySQL:

```sql
SELECT email, profile ->> '$.city' AS city
FROM users;

SELECT *
FROM users
WHERE JSON_CONTAINS(profile, '{"city": "Moscow"}');

UPDATE users
SET profile = JSON_SET(profile, '$.age', 31)
WHERE email = 'alice@example.com';
```

## 11. Массивы

PostgreSQL имеет отдельный тип массива:

```sql
SELECT *
FROM users
WHERE 'manager' = ANY (roles);

UPDATE users
SET roles = array_append(roles, 'editor')
WHERE email = 'bob@example.com'
RETURNING roles;
```

В MySQL отдельного SQL-типа массива нет. Один из вариантов — JSON:

```sql
SELECT *
FROM users
WHERE JSON_CONTAINS(roles, '"manager"');
```

Для связей и часто фильтруемых значений в обеих СУБД обычно лучше отдельная
нормализованная таблица, например `user_roles`.

## 12. Удаление с JOIN

Синтаксис отличается:

```sql
-- PostgreSQL
DELETE FROM orders AS o
USING users AS u
WHERE o.user_id = u.id
  AND u.active = false
RETURNING o.id;

-- MySQL
DELETE o
FROM orders AS o
JOIN users AS u ON u.id = o.user_id
WHERE u.active = false;
```

## 13. Транзакции, DDL и блокировки

PostgreSQL позволяет откатывать большинство обычных DDL-операций:

```sql
BEGIN;
ALTER TABLE users ADD COLUMN note text;
ROLLBACK;
```

В MySQL многие DDL-команды выполняют неявный `COMMIT`, поэтому аналогичный
`ROLLBACK` не обязан отменить `ALTER TABLE`.

Блокировка выбранных строк в обеих СУБД:

```sql
BEGIN;

SELECT id, status
FROM orders
WHERE status = 'new'
ORDER BY id
FOR UPDATE SKIP LOCKED;

-- UPDATE ...
COMMIT;
```

Такой шаблон подходит для нескольких обработчиков очереди: заблокированные
другой транзакцией строки пропускаются.

Уровень изоляции по умолчанию различается:

- PostgreSQL — `READ COMMITTED`;
- MySQL InnoDB — `REPEATABLE READ`.

Из-за этого одинаковые последовательности запросов могут видеть разные снимки
данных. Уровень изоляции для критичного кода следует задавать явно.

## 14. Индексы

Обычный B-tree индекс создаётся одинаково:

```sql
CREATE INDEX idx_orders_user_id ON orders (user_id);
```

PostgreSQL поддерживает частичный индекс:

```sql
CREATE INDEX idx_orders_unpaid
ON orders (created_at)
WHERE status = 'new';
```

MySQL не поддерживает `CREATE INDEX ... WHERE`. Близкий результат иногда
получают индексом по generated column, но схема и запрос становятся сложнее.

PostgreSQL также предоставляет специализированные типы индексов, включая GIN,
GiST и BRIN. MySQL имеет собственные механизмы, например FULLTEXT и SPATIAL;

## 15. EXPLAIN и анализ плана

PostgreSQL:

```sql
EXPLAIN ANALYZE
SELECT u.email, sum(o.amount)
FROM users AS u
JOIN orders AS o ON o.user_id = u.id
WHERE o.status = 'paid'
GROUP BY u.id, u.email;
```

MySQL:

```sql
EXPLAIN ANALYZE
SELECT u.email, sum(o.amount)
FROM users AS u
JOIN orders AS o ON o.user_id = u.id
WHERE o.status = 'paid'
GROUP BY u.id, u.email;
```

`ANALYZE` действительно выполняет запрос. Для изменяющих запросов его следует
использовать внутри транзакции с последующим `ROLLBACK` либо на тестовой базе.
Стоимость плана (`cost`) — внутренняя оценка оптимизатора; её нельзя сравнивать
между PostgreSQL и MySQL как одинаковую единицу измерения.

## 16. Дополнительные различия для миграции

### Базы данных, схемы и `search_path`

В MySQL часто используют форму `database.table` и команду `USE database`.
В PostgreSQL подключение выполняется к конкретной базе данных, а внутри неё
обычно работают со схемами:

```sql
-- PostgreSQL
SELECT * FROM public.users;
SET search_path TO public;

-- MySQL
USE shop;
SELECT * FROM shop.users;
```

Прямой запрос к таблице из другой PostgreSQL-базы через `other_db.table` не
работает. Для этого нужны отдельное подключение.

### `NULL`, сортировка и сравнение

PostgreSQL и MySQL по-разному сортируют `NULL` по умолчанию. В PostgreSQL `NULL`
считается больше обычных значений: при `ORDER BY col ASC` он окажется в конце.
В MySQL `NULL` считается меньше обычных значений и при `ASC` окажется в начале.

```sql
-- PostgreSQL
SELECT * FROM orders ORDER BY created_at ASC NULLS LAST;
SELECT * FROM users WHERE email IS NOT DISTINCT FROM 'alice@example.com';

-- MySQL: переносимый способ положить NULL в конец
SELECT * FROM orders ORDER BY created_at IS NULL, created_at ASC;
SELECT * FROM users WHERE email <=> 'alice@example.com';
```

### `UPDATE` с соединением

```sql
-- PostgreSQL
UPDATE orders AS o
SET status = 'cancelled'
FROM users AS u
WHERE u.id = o.user_id
  AND u.active = false
RETURNING o.id, o.status;

-- MySQL
UPDATE orders AS o
JOIN users AS u ON u.id = o.user_id
SET o.status = 'cancelled'
WHERE u.active = false;
```

### Ограничения и проверки

PostgreSQL поддерживает отложенную проверку внешних ключей и уникальности:

```sql
ALTER TABLE orders
ADD CONSTRAINT orders_user_id_fk
FOREIGN KEY (user_id) REFERENCES users(id)
DEFERRABLE INITIALLY DEFERRED;
```

В MySQL InnoDB внешние ключи проверяются сразу, deferrable-режима нет. Также
важно помнить, что `CHECK` в MySQL начал реально проверяться только с 8.0.16;
более старые версии принимали синтаксис, но не применяли ограничение.

### Автообновление `updated_at`

В MySQL для поля времени последнего изменения часто используют специальный
синтаксис:

```sql
-- MySQL
updated_at timestamp NOT NULL
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
```

В PostgreSQL такого атрибута столбца нет. Обычно используют триггер:

```sql
CREATE FUNCTION set_updated_at()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;

CREATE TRIGGER users_set_updated_at
BEFORE UPDATE ON users
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();
```

### Последовательности и явные id

В PostgreSQL `IDENTITY` использует отдельную последовательность. Если при
импорте данных вставлять id вручную, последовательность не обязана сама
перейти на новое максимальное значение:

```sql
INSERT INTO users (id, email, full_name)
OVERRIDING SYSTEM VALUE
VALUES (100, 'imported@example.com', 'Imported User');

SELECT setval(
    pg_get_serial_sequence('users', 'id'),
    (SELECT max(id) FROM users)
);
```

В MySQL `AUTO_INCREMENT` обычно сдвигается вперёд при вставке явного значения
больше текущего счётчика. При миграции лучше всё равно явно проверять следующий
id после загрузки данных.

### Приведение типов и арифметика

PostgreSQL строже относится к типам, а MySQL чаще выполняет неявные
преобразования с предупреждениями, особенно если `sql_mode` настроен мягко.
Отдельно проверьте деление целых чисел:

```sql
-- PostgreSQL: результат 2
SELECT 5 / 2;

-- MySQL: результат 2.5000
SELECT 5 / 2;
```

Для переносимого поведения указывайте тип явно: `5::numeric / 2` в PostgreSQL
или `DIV` в MySQL, если нужно целочисленное деление.

### Полнотекстовый поиск

Полнотекстовый поиск реализован разными механизмами:

```sql
-- PostgreSQL
CREATE INDEX idx_users_name_fts
ON users USING gin (to_tsvector('simple', full_name));

SELECT *
FROM users
WHERE to_tsvector('simple', full_name) @@ plainto_tsquery('simple', 'alice');

-- MySQL
ALTER TABLE users ADD FULLTEXT INDEX idx_users_name_fts (full_name);

SELECT *
FROM users
WHERE MATCH(full_name) AGAINST ('alice' IN NATURAL LANGUAGE MODE);
```