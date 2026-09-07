## Что такое PEST и чем он отличается от PHPUnit?

Pest — PHP-фреймворк для тестирования, построенный поверх PHPUnit. PHPUnit использует классы и методы `test*`; Pest добавляет функциональный API (`test`, `it`, `expect`), datasets, архитектурные тесты и более короткий вывод. Все PHPUnit-assertions и `TestCase` остаются доступны.

## Как установить PEST в проект через Composer?

Требуется PHP 8.4+ для актуального Pest 5.

```bash
composer require pestphp/pest --dev --with-all-dependencies
./vendor/bin/pest --init
```

Если в проекте уже есть PHPUnit, удалять его необязательно для совместной работы; при миграции можно выполнить `composer remove phpunit/phpunit` перед установкой.

## Какая базовая структура теста в PEST? Напишите простейший тест.

Файл теста содержит описание, closure с действиями и expectation.

```php
<?php

test('two plus two equals four', function () {
    expect(2 + 2)->toBe(4);
});
```

## Что делают функции test() и it() в PEST? В чём между ними разница?

Обе регистрируют тест и работают одинаково. Разница только в читаемом названии:

```php
test('user can log in', fn () => expect(true)->toBeTrue());
it('can log in', fn () => expect(true)->toBeTrue());
```

`it()` обычно читается как «it can log in», `test()` — как нейтральное имя теста.

## Как запустить тесты PEST из командной строки?

```bash
./vendor/bin/pest                 # все тесты
./vendor/bin/pest tests/Unit      # каталог
./vendor/bin/pest tests/Unit/ExampleTest.php # файл
```

## Что такое expect() и чем он отличается от assertEquals() из PHPUnit?

`expect($actual)` создаёт fluent-объект, к которому цепляются проверки. `assertEquals()` — метод PHPUnit и сравнивает два значения с нестрогим сравнением.

```php
expect(2 + 2)->toBe(4);           // строгое ===
expect(2 + 2)->toEqual(4);        // сравнение значений
$this->assertEquals(4, 2 + 2);    // PHPUnit
```

## Как в PEST пропустить выполнение теста?

```php
it('requires Redis', fn () => null)->skip('Redis недоступен в CI');

it('runs only on MySQL', fn () => null)
    ->skip(fn () => !str_contains($_SERVER['DATABASE_URL'] ?? '', 'mysql'), 'Нужен MySQL');
```

Также есть `skipOnCi()`, `skipLocally()`, `skipOnWindows()` и `skipOnPhp('>=8.5.0')`.

## Что делает функция beforeEach() и afterEach()?

`beforeEach()` выполняется перед каждым тестом файла: создаёт изолированное начальное состояние. `afterEach()` выполняется после каждого: освобождает ресурсы и очищает состояние.

```php
beforeEach(function () {
    $this->service = new UserService();
});

afterEach(function () {
    $this->service->reset();
});
```

## Как создать dataset (набор данных) для параметризованных тестов?

Объявить `dataset()` и подключить его через `with()`.

```php
dataset('positive numbers', [1, 2, 3]);

it('is positive', function (int $number) {
    expect($number)->toBeGreaterThan(0);
})->with('positive numbers');
```

## Где должны располагаться тесты PEST в структуре проекта?

Обычно в `tests/`: `tests/Unit` для изолированных unit-тестов, `tests/Functional` для интеграционных/HTTP-тестов Symfony, `tests/Arch` для архитектурных проверок и `tests/Pest.php` для общей конфигурации. Расположение suites задаётся и при необходимости меняется в `phpunit.xml`.

## Объясните, что такое Higher Order Expectations. Приведите примеры.

Это цепочка обращений к свойствам, ключам массива и методам проверяемого значения без промежуточных переменных.

```php
expect($user)
    ->name->toBe('Ivan')
    ->email->toContain('@');

expect(['name' => 'Ivan', 'roles' => ['admin', 'editor']])
    ->name->toBe('Ivan')
    ->roles->each->toBeString();
```

Higher-order expectations не зависят от фреймворка: например, `expect($user)->roles->toContain('ROLE_ADMIN');` проверит ключ или свойство `roles` без промежуточной переменной.

## Как использовать uses() для подключения traits и базовых классов?

В `tests/Pest.php`:

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

uses(WebTestCase::class)->in('Functional');
```

Это назначает Symfony `WebTestCase` всем тестам из `tests/Functional`, поэтому в них доступен HTTP-клиент через `$this->createClient()`. В новых версиях эквивалентный API: `pest()->extend(WebTestCase::class)->in('Functional');`.

## Что такое Plugins в PEST? Назовите несколько популярных плагинов.

Плагины расширяют Pest функциями, expectations, командами и CLI-опциями. Для Symfony-проекта подойдут, например, `pestphp/pest-plugin-browser`, `pestphp/pest-plugin-drift`, `pestphp/pest-plugin-mutate`, `pestphp/pest-plugin-phpstan` и `pestphp/pest-plugin-agent`.

Установка: `composer require pestphp/pest-plugin-browser --dev`.

- `pestphp/pest-plugin-browser` — предназначен для browser/E2E-тестов: переходов, кликов, ввода и проверки интерфейса.
- `pestphp/pest-plugin-drift` — помогает перенести существующие тесты с PHPUnit на Pest.
- `pestphp/pest-plugin-mutate` — выполняет mutation-тестирование, помогая найти проверки, которые не замечают ошибок в коде.
- `pestphp/pest-plugin-phpstan` — добавляет интеграцию с PHPStan для статического анализа тестового кода.
- `pestphp/pest-plugin-agent` — добавляет возможности AI-агентов для помощи в создании и сопровождении тестов.

## Как создать кастомные expectations в PEST?

Зарегистрировать метод через `expect()->extend()` в `tests/Pest.php`:

```php
expect()->extend('toBeWithinRange', function (int $min, int $max) {
    return $this->toBeGreaterThanOrEqual($min)
        ->toBeLessThanOrEqual($max);
});

expect(10)->toBeWithinRange(1, 20);
```

## Объясните разницу между describe() и test(). Когда использовать describe?

`test()` создаёт один исполняемый тест. `describe()` создаёт логическую группу тестов; в ней удобно дать общий контекст, hooks и группу.

```php
describe('Calculator', function () {
    beforeEach(fn () => $this->calculator = new Calculator());

    it('adds numbers', fn () => expect($this->calculator->add(1, 2))->toBe(3));
});
```

Используйте `describe()` для нескольких связанных сценариев, не для одного теста.

## Как работать с исключениями в PEST? Покажите несколько способов.

```php
it('throws by test modifier', function () {
    throw new DomainException('Email already exists');
})->throws(DomainException::class, 'Email already exists');

it('checks a callable', function () {
    expect(fn () => $this->service->register('bad'))
        ->toThrow(InvalidArgumentException::class);
});

it('does not throw', function () {
    $this->service->register('a@example.test');
})->throwsNoExceptions();
```

Можно также использовать PHPUnit: `$this->expectException(DomainException::class);` до вызова кода.

## Что такое arch() тесты в PEST и для чего они используются?

`arch()` проверяет правила структуры кода: зависимости, namespace, наследование, traits, запрещённые вызовы. Это не поведенческий тест, а защита архитектурных границ.

```php
arch('production code uses strict types')
    ->expect('App')
    ->toUseStrictTypes()
    ->not->toUse(['dd', 'dump']);
```

## Как настроить coverage (покрытие кода) в PEST?

Установить и включить драйвер покрытия: Xdebug (`XDEBUG_MODE=coverage`) или PCOV. Затем:

```bash
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=80
./vendor/bin/pest --coverage-html build/coverage
./vendor/bin/pest --coverage-clover build/coverage.xml
```

При необходимости ограничьте анализ кодом приложения: `--coverage-filter=app`.

## Как использовать todo() тесты и зачем они нужны?

`todo()` отмечает незавершённый сценарий, чтобы он был виден в отчёте, но не считался готовым тестом.

```php
it('sends a password-reset notification', function () {
    // будущая реализация
})->todo();
```

Используйте для запланированных проверок; не оставляйте `todo` вместо исправления падающего теста.

## Объясните работу с datasets: inline, lazy, shared. Приведите примеры.

**Inline** — данные рядом с тестом:

```php
it('validates email', fn (string $email) => expect($email)->toContain('@'))
    ->with(['a@example.test', 'b@example.test']);
```

**Lazy** — closure/generator создаёт данные только во время запуска набора; подходит для дорогих объектов:

```php
dataset('numbers', fn () => yield from [1, 2, 3]);
it('is int', fn ($n) => expect($n)->toBeInt())->with('numbers');
```

**Shared** — именованный набор в `tests/Datasets/Emails.php`, доступный тестам:

```php
// tests/Datasets/Emails.php
dataset('emails', ['a@example.test', 'b@example.test']);

// тест
it('has email', fn (string $email) => expect($email)->not->toBeEmpty())
    ->with('emails');
```

## Как создать custom matcher/expectation и зарегистрировать его глобально?

Добавить расширение в автоматически загружаемый `tests/Pest.php` (или подключённый из него `tests/Expectations.php`). Тогда оно доступно всем тестам:

```php
expect()->extend('toBeEven', function () {
    expect($this->value % 2)->toBe(0);

    return $this;
});
```

Вызов: `expect(4)->toBeEven()->toBeInt();`.

## Объясните концепцию "each" в expectations. Приведите практический пример.

`each` применяет следующую expectation к каждому элементу iterable.

```php
expect([2, 4, 6])
    ->each->toBeInt()
    ->toBeGreaterThan(0);

expect(['a' => 1, 'b' => 2])->each(
    fn ($value, $key) => $value->toBe($key === 'a' ? 1 : 2)
);
```

## Как правильно тестировать приватные методы в PEST?

Тестировать публичное поведение, которое использует приватный метод. Приватный метод — деталь реализации; прямой тест через Reflection делает тест хрупким. Если логика сложна и требует прямой проверки, выделите её в отдельный публичный класс/сервис и тестируйте его.

## Что такое Parallel Testing в PEST и как его настроить?

Pest запускает тесты в нескольких процессах, уменьшая время полного запуска.

```bash
./vendor/bin/pest --parallel
./vendor/bin/pest --parallel --processes=4
```

Тесты должны быть независимыми: отдельная БД/схема, уникальные файлы и порты, отсутствие зависимости от порядка.

## Объясните работу uses()->in() для применения настроек к группе тестов.

`in()` ограничивает область конфигурации путём или glob-паттерном относительно `tests/`:

```php
// tests/Pest.php
uses(Symfony\Bundle\FrameworkBundle\Test\WebTestCase::class)
    ->group('feature')
    ->in('Functional');
```

Настройки затронут только файлы `tests/Functional`; запуск: `./vendor/bin/pest --group=feature`.

## Как создать и использовать custom helper functions в PEST?

Определить helper в `tests/Helpers.php` и подключить в `tests/Pest.php`:

```php
// tests/Helpers.php
function validEmail(): string
{
    return 'user@example.test';
}

// tests/Pest.php
require_once __DIR__.'/Helpers.php';

// тест
expect(validEmail())->toContain('@');
```

Helpers не должны скрывать важные действия теста.

## Что такое Snapshot Testing в PEST и как его применять?

Snapshot-тест сохраняет первое значение и в следующих запусках сравнивает его с сохранённым снимком. Полезен для больших JSON/HTML-ответов.

```php
it('returns contact page', function () {
    $client = $this->createClient();
    $client->request('GET', '/contact');

    expect($client->getResponse()->getContent())->toMatchSnapshot();
});
```

Снимки лежат в `tests/.pest/snapshots`. После осознанного изменения вывода: `./vendor/bin/pest --update-snapshots`.

## Как настроить архитектурные тесты для проверки зависимостей между слоями приложения?

Создать отдельный, например `tests/Arch/LayerDependenciesTest.php`, и запретить направления зависимостей, нарушающие выбранную схему:

```php
arch('Domain does not depend on outer layers')
    ->expect('App\\Domain')
    ->not->toUse(['App\\Infrastructure', 'App\\UI', 'Symfony', 'Doctrine']);

arch('Application does not depend on UI or Infrastructure')
    ->expect('App\\Application')
    ->not->toUse(['App\\UI', 'App\\Infrastructure']);
```

Укажите реальные PSR-4 namespace проекта, а не физические пути.

## Как организовать моки и стабы в PEST? Покажите примеры с Mockery.

Mockery создаёт тестовый double. Stub задаёт возвращаемое значение; mock дополнительно проверяет взаимодействие. Закрывайте Mockery после каждого теста.

```php
use Mockery;

afterEach(fn () => Mockery::close());

it('uses a stub', function () {
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->allows('rate')->with('USD')->andReturn(92.5);

    expect($gateway->rate('USD'))->toBe(92.5);
});

it('sends an invoice once', function () {
    $mailer = Mockery::mock(Mailer::class);
    $mailer->shouldReceive('send')->once()->with(Mockery::type(Invoice::class));

    (new InvoiceService($mailer))->send(new Invoice());
});
```

## Напишите тест для API endpoint, который проверяет статус код, структуру JSON и наличие определённых полей.

Пример Symfony (`tests/Functional/UserApiTest.php`). Предполагается, что `User` — Doctrine-сущность с методами `setEmail()` и `getId()`:

```php
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

it('returns a user API resource', function () {
    $user = new User();
    $user->setEmail('user@example.test');

    $entityManager = $this->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($user);
    $entityManager->flush();

    $client = $this->createClient();
    $client->request('GET', "/api/users/{$user->getId()}");

    expect($client->getResponse()->getStatusCode())->toBe(200);

    $json = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($json)
        ->toHaveKey('data')
        ->data->id->toBe($user->getId())
        ->data->email->toBe('user@example.test');

    expect($json['data'])->not->toHaveKey('password');
});
```

## Создайте dataset с тремя разными сценариями валидации email адресов.

```php
dataset('email validation cases', [
    'valid email' => ['user@example.test', true],
    'missing at sign' => ['user.example.test', false],
    'empty value' => ['', false],
]);

it('validates email', function (string $email, bool $valid) {
    expect(filter_var($email, FILTER_VALIDATE_EMAIL) !== false)->toBe($valid);
})->with('email validation cases');
```

## Напишите архитектурный тест, который проверяет, что классы в папке Domain не зависят от классов в Infrastructure.

```php
arch('Domain has no Infrastructure dependency')
    ->expect('App\\Domain')
    ->not->toUse('App\\Infrastructure');
```

`App\\Domain` и `App\\Infrastructure` замените на namespaces своего проекта.

## Создайте custom expectation toBeValidUuid() для проверки UUID.

В `tests/Pest.php`:

```php
expect()->extend('toBeValidUuid', function () {
    expect($this->value)->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');

    return $this;
});
```

Использование: `expect('550e8400-e29b-41d4-a716-446655440000')->toBeValidUuid();`.

## Напишите тест с использованием beforeEach для настройки тестовой базы данных.

Пример Symfony: `WebTestCase` даёт доступ к контейнеру, а hook создаёт общие данные для каждого теста. Для изоляции БД используйте отдельную тестовую БД и транзакции (например, через `dama/doctrine-test-bundle`).

```php
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

uses(WebTestCase::class);

beforeEach(function () {
    $this->entityManager = $this->getContainer()->get(EntityManagerInterface::class);

    $this->user = new User();
    $this->user->setEmail('user@example.test');

    $this->entityManager->persist($this->user);
    $this->entityManager->flush();
});

it('finds the seeded user', function () {
    $user = $this->entityManager->getRepository(User::class)
        ->find($this->user->getId());

    expect($user)->not->toBeNull()
        ->email->toBe('user@example.test');
});
```

Настройки соединения `test` задаются в `.env.test` или `phpunit.xml`, не в production `.env`.

## В чём преимущества PEST перед PHPUnit?

- Меньше шаблонного кода, читаемые `it()` и `expect()`.
- Datasets, higher-order API, `arch()`, snapshots и parallel-запуск встроены.
- Полная совместимость с PHPUnit API и экосистемой.
- Удобный вывод ошибок и фильтрация.

## Когда лучше использовать PHPUnit вместо PEST?

Когда команда уже стандартизирована на PHPUnit, нужны сложные class-based hooks/наследование, или библиотека/корпоративные правила требуют именно PHPUnit-классы. Для нового проекта Pest обычно удобнее; технически он всё равно использует PHPUnit.

## Как PEST упрощает написание тестов по сравнению с традиционным подходом?

Убирает класс, `extends TestCase` и методы `test*`; действие и ожидание остаются рядом.

```php
// PHPUnit: $this->assertSame(4, 2 + 2);
// Pest:
it('adds', fn () => expect(2 + 2)->toBe(4));
```

Datasets и hooks также объявляются одной цепочкой рядом с тестом.

## Можно ли использовать PEST и PHPUnit в одном проекте? Как?

Да. Pest построен на PHPUnit и запускает PHPUnit-классы. Оставьте PHPUnit-тесты в `tests/`, добавляйте Pest-файлы рядом и запускайте всё `./vendor/bin/pest`. В Pest closure доступен `$this` с PHPUnit assertions:

```php
it('can use PHPUnit assertions', function () {
    $this->assertSame(4, 2 + 2);
});
```

## Как дебажить тесты PEST? Какие инструменты использовать?

```bash
./vendor/bin/pest tests/Functional/UserTest.php --debug
./vendor/bin/pest --filter='user can log in'
./vendor/bin/pest --bail
./vendor/bin/pest --profile
```

Используйте `dd()`/`dump()` временно, Xdebug + IDE breakpoint для пошаговой отладки, `--debug` для событий запуска и `--profile` для медленных тестов.

## Как фильтровать и запускать только определённые тесты?

```bash
./vendor/bin/pest tests/Unit/EmailTest.php
./vendor/bin/pest tests/Functional
./vendor/bin/pest --filter='validates email'
./vendor/bin/pest --group=feature
```

Группа назначается через `->group('feature')` у теста/`describe()` или в `tests/Pest.php`.

## Что делать, если тесты работают медленно? Способы оптимизации.

- Найти источник: `./vendor/bin/pest --profile`.
- Запускать независимые тесты параллельно: `--parallel --processes=4`.
- Отделить unit-тесты от интеграционных, медленные пометить группой.
- Использовать транзакции, Doctrine fixtures и минимальные тестовые данные вместо миграций и сети в каждом тесте.
- Мокировать внешние HTTP/API/очереди/почту; не использовать `sleep()`.
- Не делить между процессами БД, файлы, порты и глобальное состояние.
