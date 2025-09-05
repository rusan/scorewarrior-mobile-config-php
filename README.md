# Mobile Config — Phalcon Micro API

Сервис конфигурации для мобильных приложений. По запросу с `appVersion` и `platform` подбирает совместимые версии `assets` и `definitions` согласно правилам SemVer совместимости. Без авторизации.

## Quickstart

```bash
# Настройка окружения
cp .env.example .env

# Запуск приложения
docker compose up -d --build

# Проверка работоспособности
curl -s http://127.0.0.1:8080/health

# Запуск тестов
docker compose exec app ./vendor/bin/phpunit
```

> Если порт 8080 занят: остановите контейнер(ы), которые его используют (`docker ps`, затем `docker rm -f <container>`),
> или поправьте порт в `docker-compose.yml` (например, на `18080:8080`) и обращайтесь к `http://127.0.0.1:18080`.

## Development Setup

1. **Клонируйте репозиторий**
2. **Скопируйте `.env.example` в `.env`** - `cp .env.example .env`
3. **При необходимости** отредактируйте `.env` под свои нужды
4. **Запустите приложение** через `docker compose up -d`

## API Contract

### Endpoint

```http
GET /config?appVersion=MAJOR.MINOR.PATCH&platform=android|ios[&assetsVersion=...][&definitionsVersion=...]
Accept: application/json
```

### Правила совместимости (из ТЗ)

* `assets` совместимы, если совпадает **MAJOR** с `appVersion`.
* `definitions` совместимы, если совпадают **MAJOR** и **MINOR** с `appVersion`.
* Если `assetsVersion`/`definitionsVersion` заданы, сервис возвращает **строго эти версии**, но только если они **существуют** и **совместимы**; иначе — `404`.
* Формат всех версий: `MAJOR.MINOR.PATCH` (все три числа обязательны).

### Структура ответа (из ТЗ)

```json
{
  "backend_entry_point": { "jsonrpc_url": "api.application.com/jsonrpc/v2" },
  "assets": {
    "version": "x.y.z",
    "hash": "…",
    "urls": ["dhm.cdn.application.com","ehz.cdn.application.com"]
  },
  "definitions": {
    "version": "x.y.z",
    "hash": "…",
    "urls": ["fmp.cdn.application.com","eau.cdn.application.com"]
  },
  "notifications": { "jsonrpc_url": "notifications.application.com/jsonrpc/v1" }
}
```

### Коды ошибок (из ТЗ)

* `400` — неверные параметры (платформа или формат версии).
  Примеры сообщений:
  * `Invalid platform: desktop`
  * `Invalid version format: appVersion`
* `404` — подходящая конфигурация не найдена:
  * `Configuration not found for appVersion {appVersion} ({platform})`
* `500` — внутренняя ошибка (не ожидается в штатном потоке).

## Fixtures (данные)

* Файлы: `data/assets-fixtures.json`, `data/definitions-fixtures.json` (приложены в задании).
* Формат: по платформам (`android`/`ios`) массив объектов `{version, hash}`.
* Example download address for client: `https://dhm.cdn.application.com/${hash}/assets.zip` - domains are taken from response (see requirements).

## SemVer Policy (реализация)

* Разбор/сравнение/совместимость реализованы в `src/app/Utils/Semver.php`.
* Выбор лучшей версии — «самая новая совместимая» (по `major, minor, patch`).

## Caching & Invalidation

* **Fixtures cache**: хранится через `CacheManager` (обычно APCu) по ключу `kind+platform`, инвалидация по `mtime` файлов; счётчик `gen` увеличивается при обновлении, чтобы инвалидировать зависящие кэши.
* **Resolver cache**: кэш результата подбора для комбинации параметров + текущий `gen` фикстур.
* **Memory cache (APCu/adapter)**: внешний по отношению к запросу кэш быстрых данных.
* **LRU Cache**: локальный LRU внутри `CacheManager` для сокращения обращений к внешнему кешу в пределах процесса. Лимит настраивается через `LOCAL_CACHE_MAX_SIZE` (по умолчанию: 1000 элементов).

Примечание: локальный кэш внутри `FileCacheService` удалён — повторные чтения в рамках запроса используют `CacheManager`.

**Проверка инвалидации:**

```bash
# пока контейнер запущен с volume src и data
touch data/definitions-fixtures.json
curl -s "http://127.0.0.1:8080/config?appVersion=14.1.100&platform=android" >/dev/null
# В логах должны появиться events: fixtures_cache_invalidated → resolver_cache_set
```

## Logging

* **App JSON logs**: одна строка JSON на событие (`request_received`, `fixtures_loaded|_hit|_invalidated`, `resolver_cache_set|_hit`, `config_resolved`, `response_sent`).
* Уровень — `APP_LOG_LEVEL=info|warn|error`.
* **Все логи выводятся в STDOUT** - best practice для контейнеров, не занимают место на диске.

**Смотреть логи:**

```bash
# Логи приложения (из STDOUT контейнера)
docker compose logs -f app

# Логи в реальном времени
docker compose logs -f --tail=100 app
```

## End-to-end примеры curl

### OK (подбор лучших совместимых версий)

```bash
curl -s "http://127.0.0.1:8080/config?appVersion=14.1.100&platform=android" | jq
curl -s "http://127.0.0.1:8080/config?appVersion=14.2.100&platform=ios" | jq
```

### Явные версии (должны существовать и быть совместимы)

```bash
curl -s "http://127.0.0.1:8080/config?platform=android&appVersion=14.1.1&assetsVersion=14.3.688&definitionsVersion=14.1.487" | jq
```

### 404 (нет подходящих definitions для android + 14.2.*)

```bash
curl -i "http://127.0.0.1:8080/config?appVersion=14.2.123&platform=android"
```

Должно вернуть:
`Configuration not found for appVersion 14.2.123 (android)`.

### 400 (невалидные параметры)

```bash
curl -i "http://127.0.0.1:8080/config?appVersion=14.2&platform=ios"
curl -i "http://127.0.0.1:8080/config?appVersion=14.2.123&platform=desktop"
```

## Структура исходников

* `src/public/index.php` — точка входа в приложение
* `src/bootstrap.php` — инициализация приложения, DI, middleware (39 строк)
* `src/app/Config/` — конфигурация приложения для разных окружений
* `src/app/Controllers/` — контроллеры для обработки запросов
* `src/app/Providers/` — провайдеры для регистрации компонентов в DI
  * `ServiceProvider.php` — регистрация всех сервисов
  * `ValidatorProvider.php` — регистрация валидаторов
  * `ControllerProvider.php` — регистрация контроллеров
* `src/app/Services/` — сервисы для бизнес-логики
* `src/app/Middleware/` — middleware для обработки запросов
* `src/app/Validators/` — валидаторы для проверки входных данных
* `src/app/Utils/` — утилитные классы (Semver, Log, Http и др.)
* `data/*.json` — фикстуры версий assets и definitions по платформам

## Архитектура

Проект следует принципам **SOLID** и использует **Dependency Injection** через Phalcon DI:

### Провайдеры (Providers)

* **`ServiceProvider`** — централизованная регистрация всех сервисов в DI контейнере
* **`ValidatorProvider`** — регистрация валидаторов для проверки входных данных
* **`ControllerProvider`** — регистрация контроллеров с их зависимостями

### Сервисы (Services)

* **`ConfigService`** — основная бизнес-логика получения конфигурации
* **`ResolverService`** — разрешение зависимостей по правилам SemVer
* **`CacheManager`** — управление кэшированием (локальный LRU + внешний) с автоматическим вытеснением
* **`FileCacheService`** — кэширование файлов с инвалидацией по mtime
* **`FixturesService`** — загрузка и обработка фикстур
* (удалено) `RequestParameterService` — параметры извлекаются напрямую из `Request`

### Валидаторы (Validators)

* **`RequestValidator`** — валидация параметров запросов (platform, appVersion, версии)
* Проверка формата SemVer, валидных платформ, обязательных параметров

### Middleware

* **`LoggingMiddleware`** — логирование запросов/ответов
* **`ValidationMiddleware`** — валидация входных параметров
* **`ErrorHandlerMiddleware`** — обработка ошибок
* **`NotFoundMiddleware`** — обработка 404

### Принципы проектирования

* **Single Responsibility** — каждый класс имеет одну ответственность
* **Dependency Inversion** — зависимости инжектируются через конструкторы
* **Open/Closed** — легко расширяется новыми типами зависимостей
* **Interface Segregation** — компактные, специфичные интерфейсы

## Тестирование

Проект включает комплексное тестирование:

### Особенности тестового окружения

⚠️ **Notice Suppression**: In test/dev environments, PHP Notice errors are suppressed due to a known issue in Phalcon 5.9.2 Memory cache adapter ([GitHub issue #16747](https://github.com/phalcon/cphalcon/issues/16747)) that generates "Undefined index" warnings when accessing non-existent keys for the first time. This is a framework limitation and does not affect functionality.

### Unit тесты

* **`ConfigServiceTest`** — тестирование основной бизнес-логики
* **`FileCacheServiceTest`** — тестирование кэширования файлов
* **`FixturesServiceTest`** — тестирование загрузки фикстур
* **`ResolverServiceTest`** — тестирование разрешения зависимостей
* **`RequestValidatorTest`** — тестирование валидации входных данных
* **`CacheManagerTest`** — тестирование LRU кэша и управления памятью

### Smoke тесты

* **`ApiSmokeTest`** — end-to-end тестирование API endpoints
* Проверка всех сценариев: валидные запросы, ошибки 400/404, кэширование

### Покрытие

* **79 тестов, 218 assertions**
* Все тесты проходят в Docker окружении
* Используется PHPUnit 10.x с современными практиками

### Запуск тестов

```bash
# Все тесты
docker compose exec app ./vendor/bin/phpunit

# С подробным выводом
docker compose exec app ./vendor/bin/phpunit --testdox

# С покрытием кода
docker compose exec app ./vendor/bin/phpunit --coverage-html coverage/
```

> Примечание: e2e smoke-тесты обращаются к `http://127.0.0.1:8080`. Если вы запускаете PHPUnit вне Docker или на другом порту, задайте переменную окружения `BASE_URL`,
> например: `BASE_URL=http://127.0.0.1:18080 ./vendor/bin/phpunit`.

## Docker

* `Dockerfile` — образ с PHP 8.2, Phalcon 5.9, nginx
* `docker-compose.yml` — конфигурация для запуска (монтирует `./data` в `/local/data`)

**Управление:**

```bash
# Запуск
docker compose up -d --build

# Остановка
docker compose down

# Логи
docker compose logs -f

# Пересборка
docker compose build --no-cache

# Тесты
docker compose exec app ./vendor/bin/phpunit

# Тесты с подробным выводом
docker compose exec app ./vendor/bin/phpunit --testdox

# Тесты с покрытием кода
docker compose exec app ./vendor/bin/phpunit --coverage-html coverage/
```
