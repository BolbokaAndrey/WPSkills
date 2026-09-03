# Теория Nginx

## Что такое Nginx?

Nginx — веб-сервер и обратный прокси-сервер.

- Отдает статические файлы.
- Принимает HTTPS.
- Передает запросы приложению или PHP-FPM.
- Балансирует нагрузку и кеширует ответы.

## Зачем использовать Nginx?

- Быстро отдавать статику.
- Принимать HTTPS в одной точке.
- Направлять запросы на внутренние серверы.
- Ограничивать размер запросов, число запросов и подключений.
- Распределять нагрузку между несколькими серверами.

## Почему у Nginx такая высокая производительность?

- Событийная неблокирующая обработка ввода-вывода.
- Один рабочий процесс обслуживает много соединений.
- Нет отдельного процесса или потока на каждое соединение.
- Используются возможности ОС для работы с сетью и файлами.

## Как Nginx обрабатывает запросы?

1. Главный процесс читает конфигурацию и запускает рабочие процессы.
2. Рабочий процесс принимает соединение и читает запрос.
3. Выбирается блок `server` по адресу, порту и заголовку `Host`.
4. Выбирается подходящий блок `location`.
5. Nginx отдает файл, делает перенаправление или передает запрос приложению.
6. Ответ отправляется клиенту и записывается в журнал.

## Что такое прямой прокси и обратный прокси?

- Прямой прокси стоит между клиентом и внешними сайтами. Он обращается к сайту от имени клиента.
- Обратный прокси стоит между клиентом и серверами приложения. Он принимает запрос клиента и передает его внутреннему серверу.

## Каковы преимущества использования «обратного прокси-сервера»?

- Одна точка входа для HTTPS, перенаправлений и ограничений.
- Внутренние серверы не доступны напрямую из Интернета.
- Можно распределять запросы между несколькими серверами.
- Статику и кеш можно вынести с сервера приложения.
- Внутренние серверы можно менять без изменения адреса сайта.

## В чем преимущества и недостатки Nginx?

Преимущества:

- Высокая скорость работы с большим числом соединений.
- Небольшое потребление памяти.
- Удобная отдача статики, HTTPS, проксирование и балансировка.

Недостатки:

- Не выполняет код приложения. Для PHP нужен PHP-FPM.
- Сложную прикладную логику в конфигурации реализовывать неудобно.
- После изменения конфигурации нужна проверка `nginx -t` и перезагрузка.

## Где располагаются конфиги, структура папки с конфигами

На Debian/Ubuntu основной файл: `/etc/nginx/nginx.conf`.

```text
/etc/nginx/
├── nginx.conf          # основной файл
├── mime.types          # типы файлов
├── conf.d/*.conf       # дополнительные настройки
├── sites-available/    # конфиги сайтов
├── sites-enabled/      # включенные сайты
└── snippets/           # общие фрагменты
```

На RHEL-подобных системах обычно используют `/etc/nginx/nginx.conf` и `/etc/nginx/conf.d/*.conf`.

Проверка: `nginx -t`. Применение: `systemctl reload nginx`.

## Что нужно указать в конфиге для отдачи статичного сайта

Нужны директивы `listen`, `server_name`, `root`, `index` и `location`.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example/public;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

У Nginx должны быть права на чтение каталога и файлов.

## Для чего нужен location в конфиге

`location` задает правила для части адресов сайта.

- Отдать файл.
- Сделать перенаправление.
- Передать запрос PHP-FPM или другому серверу.
- Установить заголовки, кеширование и ограничения.

## Как указать location только для php скриптов

```nginx
location ~ \.php$ {
    try_files $uri =404;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
}
```

`try_files $uri =404` не передает в PHP-FPM несуществующий файл.

## Как настроить виртуальный хост Nginx?

1. Создать файл, например `/etc/nginx/sites-available/example.conf`.
2. Добавить блок `server`.
3. Создать ссылку на него в `/etc/nginx/sites-enabled/`.
4. Выполнить `nginx -t` и `systemctl reload nginx`.

```nginx
server {
    listen 80;
    server_name example.com www.example.com;
    root /var/www/example/public;

    location / { try_files $uri $uri/ =404; }
}
```

## Что означает ERR_TOO_MANY_REDIRECTS

Ошибка браузера: сайт зациклил перенаправления.

Частая причина: Nginx перенаправляет HTTP на HTTPS, а приложение за прокси снова считает запрос HTTP. Нужно передавать приложению исходную схему:

```nginx
proxy_set_header X-Forwarded-Proto $scheme;
```

## Как задать максимальный размер файла отправляемого на сервер

Использовать `client_max_body_size` в блоке `http`, `server` или `location`.

```nginx
server {
    client_max_body_size 100m;
}
```

При превышении лимита Nginx вернет код `413`.

## Как увеличить таймауты

- Для клиента: `client_header_timeout`, `client_body_timeout`, `send_timeout`.
- Для прокси: `proxy_connect_timeout`, `proxy_send_timeout`, `proxy_read_timeout`.
- Для PHP-FPM: `fastcgi_connect_timeout`, `fastcgi_send_timeout`, `fastcgi_read_timeout`.

```nginx
location /api/ {
    proxy_connect_timeout 10s;
    proxy_send_timeout 60s;
    proxy_read_timeout 60s;
    proxy_pass http://api;
}
```

## Какие есть регулярки для location

- `location = /path` — точное совпадение.
- `location /path` — совпадение по началу адреса.
- `location ^~ /assets/` — совпадение по началу адреса без проверки регулярных выражений.
- `location ~ \.php$` — регулярное выражение с учетом регистра.
- `location ~* \.(jpg|png)$` — регулярное выражение без учета регистра.

Порядок выбора: точное совпадение → самый длинный префикс → регулярные выражения в порядке записи. `^~` отменяет проверку регулярных выражений.

## Как ограничить кол-во запросов в секунду/минуту

```nginx
http {
    limit_req_zone $binary_remote_addr zone=per_ip:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=60r/m;

    server {
        location /api/ {
            limit_req zone=per_ip burst=20 nodelay;
            limit_req_status 429;
        }
    }
}
```

- `rate=10r/s` — 10 запросов в секунду.
- `rate=60r/m` — 60 запросов в минуту.
- `burst=20` — допустимый краткий всплеск в 20 запросов.
- `nodelay` — запросы из всплеска не задерживаются.

## Ограничение кол-ва подключений

```nginx
http {
    limit_conn_zone $binary_remote_addr zone=per_ip_conn:10m;

    server {
        limit_conn per_ip_conn 20;
        limit_conn_status 429;
    }
}
```

Пример ограничивает клиента до 20 одновременных подключений. В HTTP/2 и HTTP/3 одновременно выполняемые запросы учитываются отдельно.

## Как реализован алгоритм балансировки нагрузки Nginx и какие стратегии?

Серверы указываются в блоке `upstream`.

```nginx
upstream app_pool {
    least_conn;
    server app1:8080;
    server app2:8080;
}
```

Стратегии:

- По очереди — используется по умолчанию; можно задать вес `weight`.
- `least_conn` — на сервер с наименьшим числом активных соединений.
- `ip_hash` — один клиент обычно попадает на один сервер.
- `hash ключ consistent` — выбор сервера по значению ключа, например адресу запроса.
- `random` — случайный выбор.

Параметры сервера: `weight`, `max_fails`, `fail_timeout`, `backup`, `down`, `max_conns`.

## Какие глобальные переменные есть в nginx

Основные встроенные переменные:

- Запрос: `$request`, `$request_method`, `$request_uri`, `$uri`, `$args`, `$query_string`, `$request_time`.
- Клиент: `$remote_addr`, `$remote_port`, `$remote_user`, `$http_user_agent`, `$cookie_session`.
- Сервер: `$host`, `$server_name`, `$server_addr`, `$server_port`, `$scheme`, `$https`.
- Файл: `$document_root`, `$request_filename`.
- Ответ: `$status`, `$body_bytes_sent`, `$sent_http_content_type`.
- Внутренний сервер: `$upstream_addr`, `$upstream_status`, `$upstream_response_time`.
- Время: `$time_local`, `$time_iso8601`, `$msec`.

`$http_user_agent` содержит заголовок `User-Agent`, `$cookie_session` — cookie `session`, `$sent_http_content_type` — заголовок ответа `Content-Type`.
