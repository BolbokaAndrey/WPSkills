## Что такое паттерн UseCase?

UseCase (он же Interactor в Clean Architecture) — класс, который инкапсулирует один конкретный сценарий взаимодействия пользователя (или другой системы) с приложением. Название класса — это глагол действия: `GetTeamPage`, `FormSubscribe`, `CreateOrder`, `CancelSubscription`.

Ключевые характеристики:

- **Один публичный метод.** Обычно называется `process()`, `execute()` или `handle()`. Если у класса появляется второй осмысленный публичный метод — это сигнал, что нужно выделять второй UseCase.
- **Не содержит инфраструктурных деталей.** UseCase не знает, как именно данные хранятся в БД (это работа Repository) и не формирует HTTP-ответ (это работа контроллера) — он только оркестрирует бизнес-логику между ними.
- **Явные зависимости через конструктор.** Repository и Service внедряются в конструктор, что делает зависимости UseCase видимыми и тестируемыми.
- **Входные и выходные данные — типизированные объекты (DTO).** UseCase принимает DTO/примитивы и возвращает DTO — не «сырые» массивы и не ORM-сущности напрямую, чтобы не протекала внутренняя модель данных наружу.
- **Не переиспользуется другими UseCase напрямую.** Если два сценария используют одну и ту же логику — эта логика выносится в Service или Repository, а не в вызов одного UseCase из другого.

Пример (проект Upgreat.one, модуль `upgreat.pages`):

```php
namespace Upgreat\Pages\UseCase;

final readonly class FormSubscribe implements IFormSubscribe
{
    public function __construct(
        private HighloadblockRepository $hlbRepository = new HighloadblockRepository(),
        private SubscriptionRepository $subscriptionRepository = new SubscriptionRepository(),
    ) {
    }

    public function process(BxapiSubscribeFormSendPostBodyDto $requestDto): void
    {
        Loader::includeModule('subscribe');

        $email = filter_var($requestDto->email, FILTER_VALIDATE_EMAIL);
        if (!$email || !$requestDto->consent) {
            return;
        }

        $contestObj = $this->hlbRepository->getContestById($requestDto->id);
        $rubricId = $this->subscriptionRepository->getOrCreateRubric($contestObj);
        $subscriberId = $this->subscriptionRepository->getOrCreateSubscription($email);

        $this->subscriptionRepository->createSubscription($subscriberId, $rubricId);
    }
}
```

Класс делает одну вещь — оформляет подписку из данных формы; вся работа с данными делегирована репозиториям.

### Чем UseCase отличается от Service?

- **UseCase** — это конкретный сценарий («глагол + объект»), привязанный к одной точке входа приложения (обычно один HTTP-эндпоинт = один UseCase). Он оркестрирует, но сам не содержит переиспользуемой доменной логики.
- **Service** — переиспользуемый кусок бизнес-логики или обёртка над доменной сущностью, которая может использоваться внутри нескольких UseCase. Service отвечает на вопрос «что умеет делать эта часть предметной области», а не «какой сценарий сейчас выполняется».

Если провести аналогию с Clean Architecture: UseCase — это слой Application (сценарии использования), Service — чаще относится к слою Domain (или к Service Layer, если рассматривать классическую трёхслойную архитектуру).

## Что такое Service Layer?

Service Layer (слой сервисов) — архитектурный паттерн (описан Мартином Фаулером в *Patterns of Enterprise Application Architecture*), который определяет границу приложения и набор доступных операций с точки зрения клиентского кода (контроллера, CLI-команды, другого сервиса), координируя ответ приложения в каждой операции.

Что делает Service Layer:

- **Инкапсулирует бизнес-правила и оркестрацию**, которые не относятся напрямую ни к одной доменной сущности целиком, а требуют координации нескольких сущностей/репозиториев.
- **Не занимается доступом к данным напрямую** — для этого используется Repository; Service опирается на Repository, но не содержит SQL/ORM-запросов сам.
- **Не занимается представлением/транспортом** — не формирует HTTP-ответ, не работает с `$_REQUEST`, не знает о шаблонах.
- **Может быть переиспользован** разными UseCase, контроллерами, консольными командами — в отличие от UseCase, который обычно привязан к одному сценарию/эндпоинту.

Пример Service из Upgreat.one — обёртка над данными инфоблока «О проекте», используемая при сборке главной страницы:

```php
namespace Upgreat\Pages\Services\MainPage;

final class AboutService
{
    private ?EO_ElementIndexAbout $iblock;

    public function __construct(IndexRepository $indexRepository)
    {
        $this->iblock = $indexRepository->getIndexAbout();
        CacheHelper::registerTag(ElementIndexAboutTable::getEntity()->getIblock()->getId());
    }

    public function isNotNull(): bool
    {
        return isset($this->iblock);
    }

    public function getButton(): CommonButtonDto
    {
        $buttonTitle = $this->iblock->getButtonTitle()?->getValue() ?? '';
        $buttonLink = $this->iblock->getButtonLink()?->getValue() ?? '';

        return new CommonButtonDto($buttonTitle, $buttonLink);
    }

    public function getMetrics(): DetailComponentsAboutBlockMetricsItemDtoCollection
    {
        // сборка коллекции метрик из полей инфоблока
    }
}
```

`AboutService` не знает про HTTP и про конкретный UseCase, который его вызывает — он просто предоставляет удобный объектный доступ к данным одной доменной области. Несколько разных UseCase (например, «главная страница» и «страница о команде») могут использовать один и тот же Service, не дублируя логику извлечения и форматирования данных.

### Соотношение слоёв: Controller → UseCase → Service → Repository

| Слой | Отвечает за | Знает про HTTP | Знает про БД/ORM | Переиспользуется |
|---|---|---|---|---|
| Controller | приём запроса, вызов нужного UseCase, формирование ответа | да | нет | нет (1 action = 1 route) |
| UseCase | один сценарий использования, оркестрация Service/Repository | нет | нет | нет (1 UseCase = 1 сценарий) |
| Service | переиспользуемая доменная логика | нет | опосредованно, через Repository | да, между UseCase |
| Repository | доступ к данным (БД, инфоблоки, highload-блоки, внешние API) | нет | да | да |

## Структура backend API-приложения на основе UseCase

За основу взята реальная структура backend-приложения Upgreat.one (Bitrix D7 + собственный роутинг + слой, сгенерированный из OpenAPI-спецификации).

### Общая схема прохождения запроса

```
HTTP-запрос
   │
   ▼
routes.php (RoutingConfigurator)      — определяет маршрут → [Controller, action]
   │
   ▼
Controller::actionAction()            — тонкий, только достаёт UseCase из DI и вызывает process()
   │
   ▼
UseCase::process()                    — оркестрирует сценарий, возвращает DTO
   │            │
   ▼            ▼
Service     Repository                — переиспользуемая логика / доступ к данным
   │            │
   └─────┬──────┘
         ▼
   Bitrix ORM / Highload-блоки / БД
```

### Слой генерации API (модуль `webpractik.bitrixgen`)

Контроллеры, интерфейсы UseCase и DTO генерируются из OpenAPI-спецификации — это гарантирует, что контракт API и код не расходятся.

```
webpractik.bitrixgen/
└── lib/
    ├── Controllers/
    │   ├── AbstractController.php     — общая логика: валидация, обработка исключений, сборка DTO из запроса
    │   ├── PagesController.php        — тонкие action-методы конкретных страниц
    │   └── ...
    ├── Interfaces/
    │   ├── IGetTeamPage.php           — контракт: что принимает и что возвращает process()
    │   ├── IFormSubscribe.php
    │   └── ...
    ├── Dto/
    │   ├── PagesTeamBodyDto.php       — типизированные объекты ответа
    │   └── Collection/                — типизированные коллекции DTO
    └── UseCase/                       — сгенерированные УseCase-заглушки (используются, если модуль
                                          с реальной реализацией не установлен)
```

Контроллер — только точка входа, вся работа делегирована UseCase, который достаётся из DI-контейнера по интерфейсу:

```php
namespace Webpractik\Bitrixgen\Controllers;

class PagesController extends AbstractController
{
    public function getTeamPageAction()
    {
        $serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
        $class = $serviceLocator->get('webpractik.bitrixgen.getTeamPage');

        return new \Bitrix\Main\Engine\Response\Json($class->process());
    }
}
```

Регистрация в DI (`.settings.php`) — реальная реализация из бизнес-модуля переопределяет сгенерированную заглушку, если она есть:

```php
if (!$serviceLocator->has('webpractik.bitrixgen.getTeamPage')) {
    $serviceValue['webpractik.bitrixgen.getTeamPage'] = ['className' => GetTeamPage::class];
}
```

### Слой бизнес-логики (модуль `upgreat.pages`)

```
upgreat.pages/
└── lib/
    ├── UseCase/
    │   ├── GetTeamPage.php            — implements IGetTeamPage, один сценарий = одна страница/действие
    │   ├── FormSubscribe.php          — implements IFormSubscribe
    │   ├── GetIndexPage.php
    │   └── Components/                — UseCase для переиспользуемых блоков (виджетов) на разных страницах
    ├── Services/
    │   └── MainPage/
    │       ├── AboutService.php       — переиспользуемая логика одного домена главной страницы
    │       ├── SliderService.php
    │       └── NewsService.php
    ├── Repositories/
    │   ├── IblockRepository.php       — доступ к инфоблокам Bitrix
    │   ├── HighloadblockRepository.php
    │   ├── ContentRepository.php
    │   └── SubscriptionRepository.php
    ├── Builders/                      — сборка составных DTO из нескольких Service (Builder pattern)
    ├── Helpers/                       — статический хелперы без состояния (кеш, форматирование текста)
    └── Events/                        — обработчики событий Bitrix (инвалидация кеша и т.п.)
```

`GetTeamPage` — конкретная реализация UseCase, зависящая только от репозиториев:

```php
namespace Upgreat\Pages\UseCase;

final readonly class GetTeamPage implements \Webpractik\Bitrixgen\Interfaces\IGetTeamPage
{
    public function __construct(
        private IblockRepository $iblockRepository = new IblockRepository(),
        private ContentRepository $contentRepository = new ContentRepository(),
        private HighloadblockRepository $highloadblockRepository = new HighloadblockRepository(),
        private ContestRepository $contestRepository = new ContestRepository(),
    ) {
    }

    public function process(): null|PagesTeamBodyDto
    {
        // кеширование результата + сборка DTO из данных репозиториев
        return $this->buildBody();
    }
}
```

### Почему структура именно такая

- **`webpractik.bitrixgen` (сгенерированный слой) отделён от `upgreat.pages` (бизнес-логика).** OpenAPI-контракт можно перегенерировать в любой момент, не затрагивая ручной код бизнес-логики — контракт (интерфейсы, DTO) и реализация физически разнесены по разным модулям.
- **UseCase зависит от интерфейса, а не наоборот** (`GetTeamPage implements IGetTeamPage`, интерфейс определён в сгенерированном модуле) — это инверсия зависимостей: бизнес-модуль подстраивается под контракт API, а не контракт подстраивается под реализацию.
- **DI/Service Locator разруливает, какая реализация используется** — если бизнес-модуль ещё не реализовал конкретный UseCase, используется сгенерированная заглушка, что позволяет фронтенду и бэкенду разрабатываться параллельно от одного контракта.
- **Repository — единственная точка доступа к данным.** UseCase и Service не обращаются к Bitrix ORM напрямую, а идут через Repository — это позволяет менять способ хранения данных, не трогая бизнес-логику, и упрощает тестирование (Repository можно подменить мок-объектом).
- **Service выносится отдельно от UseCase, когда логика нужна нескольким сценариям** — в проекте это видно на примере `Services/MainPage/*`, где несколько мелких сервисов (`AboutService`, `SliderService`, `NewsService`) собираются в один составной DTO билдером (`MainPageBodyBuilder`) внутри `GetIndexPage`, но каждый Service остаётся независимым и может быть переиспользован в другом UseCase.

## Итоговые преимущества UseCase-подхода

- **Тестируемость** — каждый сценарий изолирован в отдельном классе с явными зависимостями, легко покрывается unit-тестом с мок-репозиториями.
- **Читаемость и навигация** — по названию файла в папке `UseCase/` сразу понятно, какие сценарии есть в приложении, не нужно искать логику внутри «толстых» контроллеров или моделей.
- **Слабая связанность (Low Coupling)** — контроллер и фреймворк подключены только к UseCase через интерфейс, бизнес-логика не знает о фреймворке.
- **Параллельная разработка** — контракт (интерфейс + DTO), сгенерированный из OpenAPI, позволяет фронтенду и бэкенду работать независимо, ориентируясь на один источник правды (спецификацию).
- **Явный источник роста сложности** — если UseCase «разбухает», это явный сигнал выносить куски логики в Service, а не размазывать её по контроллеру или модели.
