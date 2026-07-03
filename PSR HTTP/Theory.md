Все четыре стандарта решают одну проблему — разрозненность HTTP-экосистемы PHP.
До них каждый фреймворк изобретал свои классы Request и Response, и библиотеки не могли работать друг с другом без адаптеров.
PHP-FIG (Framework Interoperability Group) выпустил эту группу стандартов, чтобы любой компонент —
роутер, middleware, HTTP-клиент — мог взаимодействовать с любым другим через единый контракт.

## PSR-7 — HTTP Message Interfaces ##

PSR-7 описывает, как должны выглядеть HTTP-сообщения в PHP.

Главное архитектурное решение PSR-7 — все объекты **неизменяемые (immutable)**. 
Любой метод, который «изменяет» объект, на самом деле возвращает новый экземпляр с изменением, не трогая оригинал.

### Стандарт определяет семь интерфейсов: ###

1. `Psr\Http\Message\MessageInterface` — базовый интерфейс для всех HTTP-сообщений. Описывает работу с заголовками (`getHeaders`, `withHeader`, `hasHeader`) и телом сообщения (`getBody`, `withBody`).
2. `Psr\Http\Message\RequestInterface` — интерфейс для HTTP-запросов. Добавляет метод (`getMethod`, `withMethod`) и URI (`getUri`, `withUri`).
3. `Psr\Http\Message\ResponseInterface` — интерфейс для HTTP-ответов. Добавляет `getStatusCode`, `withStatus`, `getReasonPhrase`.
4. `Psr\Http\Message\ServerRequestInterface` — интерфейс для HTTP-запросов, которые пришли на сервер. Расширяет RequestInterface, добавляя данные окружения сервера. Добавляет атрибуты запроса для middleware
5. `Psr\Http\Message\UriInterface` — интерфейс для URI. Разбивает адрес на компоненты: `getScheme`, `getHost`, `getPort`, `getPath`, `getQuery`, `getFragment` — и позволяет создавать новый URI через with-методы.
6. `Psr\Http\Message\StreamInterface` — интерфейс для потоков данных с методами `read`, `write`, `seek`, `tell`, `eof`, `getContents`.
7. `Psr\Http\Message\UploadedFileInterface` — интерфейс для загруженных файлов. Хранит поток данных, имя, размер, MIME-тип и код ошибки загрузки.

## PSR-15 — HTTP Server Request Handlers ##

PSR-15 описывает, как должны выглядеть HTTP-обработчики запросов на сервере.

### Стандарт определяет два интерфейса: ###

1. `Psr\Http\Server\RequestHandlerInterface` - конечный обработчик запроса. Принимает `ServerRequestInterface`, возвращает `ResponseInterface`
2. `Psr\Http\Server\MiddlewareInterface` - промежуточный обработчик запроса. Принимает запрос и следующий обработчик в цепочке, может как продолжить цепочку ($handler->handle($request)), так и оборвать её, вернув собственный ответ.

## PSR-17 — HTTP Factories ##

PSR-17 описывает, как должны выглядеть фабрики для создания PSR-7 объектов.

### Стандарт определяет шесть интерфейсов: ###

1. `Psr\Http\Message\RequestFactoryInterface` - фабрика для создания запросов. `createRequest(string $method, $uri): RequestInterface`
2. `Psr\Http\Message\ResponseFactoryInterface` - фабрика для создания ответов. `createResponse(int $code, string $reasonPhrase): ResponseInterface`
3. `Psr\Http\Message\ServerRequestFactoryInterface` - фабрика для создания серверных запросов. `createServerRequest(string $method, $uri, array $serverParams): ServerRequestInterface`
4. `Psr\Http\Message\StreamFactoryInterface` - фабрика для создания потоков данных. `createStream(string $content): StreamInterface`, `createStreamFromFile(string $filename): StreamInterface, createStreamFromResource($resource): StreamInterface`
5. `Psr\Http\Message\UriFactoryInterface` - фабрика для создания URI. `createUri(string $uri): UriInterface`
6. `Psr\Http\Message\UploadedFileFactoryInterface` - фабрика для создания загруженных файлов. `createUploadedFile(StreamInterface $stream, int $size, int $error, string $clientFilename, string $clientMediaType): UploadedFileInterface`

## PSR-18 — HTTP Client ##

PSR-18 описывает, как должны выглядеть HTTP-клиенты. Стандартизирует отправку HTTP-запросов наружу — к сторонним API, сервисам, микросервисам

### Стандарт определяет один интерфейс: ###

1. `Psr\Http\Client\ClientInterface` - клиент для отправки запросов. Принимает PSR-7 RequestInterface, возвращает PSR-7 ResponseInterface. `sendRequest(RequestInterface $request): ResponseInterface`

### Определяет три типа исключений ###

1. `Psr\Http\Client\ClientExceptionInterface` - исключение, которое может быть выброшено клиентом (базовое).
2. `Psr\Http\Client\NetworkExceptionInterface` - исключение, которое может быть выброшено клиентом при недоступности сети.
3. `Psr\Http\Client\RequestExceptionInterface` - исключение, которое может быть выброшено клиентом при ошибке запроса.
