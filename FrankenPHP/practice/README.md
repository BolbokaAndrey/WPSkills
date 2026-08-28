
```bash
docker compose -f compose.yaml -f compose.browser.yaml up --detach
curl -i http://localhost:8080/
curl -i http://localhost:8081/
docker compose down
```

- `http://localhost:8080/` — FrankenPHP.
- `http://localhost:8081/` — nginx + php-fpm.

Файл `compose.browser.yaml` публикует порты только для браузера и ручного `curl`. Скрипт измерений его не использует, поэтому не конфликтует с занятыми портами хоста.

Если один из портов занят, задайте другой при запуске:

```bash
NGINX_BROWSER_PORT=8082 \
  docker compose -f compose.yaml -f compose.browser.yaml up --detach
```

В этом случае nginx + php-fpm будет доступен по адресу `http://localhost:8082/`.

## Измерение

```bash
chmod +x practice/measure.sh
./practice/measure.sh
```

Скрипт использует отдельный контейнер `benchmark-client` внутри Docker-сети, поэтому не зависит от доступа среды запуска к `localhost:8080` и `localhost:8081`.

Скрипт измеряет:

- cold-start: время от запуска контейнеров до первого успешного HTTP-ответа;
- latency: минимум, среднее, максимум и p95 для 100 последовательных HTTP-запросов.

Для другого числа запросов задайте переменную `REQUESTS`, например `REQUESTS=300 ./practice/measure.sh`.

Повторите измерение несколько раз. Первый запуск может быть медленнее из-за скачивания Docker-образов. Это синтетический учебный тест: он показывает эффект повторной инициализации приложения. Для реального решения дополнительно измеряйте Symfony/API Platform, базу данных и внешние сервисы.

Если сервер не отвечает за 30 секунд, скрипт завершится и покажет последние 50 строк логов проблемного контейнера.

## Проверка SSE

После запуска контейнеров выполните:

```bash
curl -i --no-buffer http://localhost:8080/sse.php
```

Команда должна показать три SSE-события с интервалом в одну секунду. Для nginx + php-fpm используйте порт `8081`.

В браузере можно открыть `http://localhost:8080/sse.php` и проверить заголовок `Content-Type: text/event-stream` во вкладке Network DevTools.

Если порты `localhost` недоступны из среды запуска, используйте внутренний Docker-запрос:

```bash
docker compose exec --no-TTY benchmark-client \
  curl -i --no-buffer http://frankenphp/sse.php
```

## HTTP/3

Для HTTP/3 подготовлен отдельный файл `compose.http3.yaml`. Основной файл для измерений не изменяется.

1. Укажите публичный домен в DNS: его A/AAAA-записи должны вести на сервер с Docker.
2. Откройте в firewall и пробросьте на сервер `TCP 80`, `TCP 443` и `UDP 443`.
3. Запустите FrankenPHP с HTTP/3:

```bash
SITE_ADDRESS=example.com \
  docker compose -f compose.yaml -f compose.http3.yaml up --detach frankenphp
```

4. Откройте `https://example.com` в Chrome или Edge.
5. DevTools → Network → правой кнопкой по заголовкам таблицы → включите **Protocol**. Значение `h3` подтверждает HTTP/3.

Для локального запуска вместо домена можно использовать `SITE_ADDRESS=localhost`. Браузеру потребуется доверять локальному центру сертификации Caddy; для первой проверки HTTP/3 проще использовать публичный домен.
