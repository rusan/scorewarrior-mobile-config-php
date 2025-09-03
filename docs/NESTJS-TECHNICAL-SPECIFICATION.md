# Техническое задание: Mobile Config API на NestJS

## Общее описание задачи

Необходимо создать HTTP API сервис на **Node.js 22 + NestJS**, который будет отдавать конфигурацию для мобильных приложений на основе версии приложения и платформы. Сервис должен быть функционально эквивалентен существующему PHP/Phalcon решению.

## Техническое окружение

- **Runtime**: Node.js 22.x
- **Framework**: NestJS 10.x
- **Language**: TypeScript 5.x
- **Package Manager**: npm
- **Testing**: Jest + Supertest
- **Documentation**: Swagger/OpenAPI
- **Containerization**: Docker

## Задача 1: Инициализация проекта и базовая структура

### 1.1 Создание проекта

**Что делать:**
1. Создать новый NestJS проект с именем `mobile-config-nestjs`
2. Установить необходимые зависимости
3. Настроить TypeScript конфигурацию
4. Создать базовую структуру папок

**Команды для выполнения:**
```bash
npm i -g @nestjs/cli
nest new mobile-config-nestjs --package-manager npm
cd mobile-config-nestjs
```

**Дополнительные зависимости для установки:**
```bash
npm install @nestjs/config @nestjs/cache-manager cache-manager
npm install class-validator class-transformer
npm install @nestjs/swagger swagger-ui-express
npm install joi uuid
npm install --save-dev @types/uuid @types/jest supertest @types/supertest
```

**Требования к структуре папок в src/:**
```
src/
├── config/           # Конфигурация приложения
├── modules/          # Модули NestJS (config, health)  
├── services/         # Бизнес-сервисы
├── utils/            # Утилитные классы
├── middleware/       # Middleware
├── exceptions/       # Кастомные исключения
├── types/            # TypeScript типы и интерфейсы
├── app.module.ts     # Главный модуль
└── main.ts           # Точка входа
```

**Критерии готовности:**
- Проект создан и зависимости установлены
- Структура папок соответствует требованиям
- `npm run start:dev` запускает приложение без ошибок
- Available endpoint `http://localhost:3000` (standard NestJS welcome)

---

## Задача 2: Конфигурация и типы данных

### 2.1 Создание TypeScript типов

**Файл:** `src/types/config.types.ts`

**Требования:**
1. Создать интерфейс `FixtureItem` с полями:
   - `version: string` - версия в формате SemVer
   - `hash: string` - SHA256 хеш

2. Создать интерфейс `PlatformFixtures`:
   - `android: FixtureItem[]`
   - `ios: FixtureItem[]`

3. Создать интерфейс `ConfigResponse` точно по структуре из PHP проекта:
   - `backend_entry_point: { jsonrpc_url: string }`
   - `assets: { version: string, hash: string, urls: string[] }`
   - `definitions: { version: string, hash: string, urls: string[] }`
   - `notifications: { jsonrpc_url: string }`

4. Создать интерфейс `ErrorResponse`:
   - `error: { code: number, message: string }`

5. Создать тип `Platform` как union: `'android' | 'ios'`

### 2.2 Конфигурация приложения

**Файл:** `src/config/configuration.ts`

**Требования:**
Создать функцию `export default () => ({})` которая возвращает объект с:

1. **Основные настройки:**
   - `port` - из `process.env.PORT` или 3000 по умолчанию
   - `nodeEnv` - из `process.env.NODE_ENV` или 'development'
   - `logLevel` - из `process.env.LOG_LEVEL` или 'info'

2. **URL конфигурация (точно как в PHP):**
   - `urls.backendJsonRpc: 'api.application.com/jsonrpc/v2'`
   - `urls.notificationsJsonRpc: 'notifications.application.com/jsonrpc/v1'`
   - `urls.assets: ['dhm.cdn.application.com', 'ehz.cdn.application.com']`
   - `urls.definitions: ['fmp.cdn.application.com', 'eau.cdn.application.com']`

3. **Пути к фикстурам:**
   - `fixtures.assetsPath` - из env или './data/assets-fixtures.json'
   - `fixtures.definitionsPath` - из env или './data/definitions-fixtures.json'

4. **Настройки кеша:**
   - `cache.ttl` - из env или 3600 секунд
   - `cache.max` - из env или 1000 элементов

**Файл:** `src/config/validation.schema.ts`

**Требования:**
Создать Joi схему валидации переменных окружения:
- `NODE_ENV` - enum ['development', 'production', 'test'], default 'development'
- `PORT` - number, default 3000
- `LOG_LEVEL` - enum ['error', 'warn', 'info', 'debug'], default 'info'
- `CACHE_TTL` - number, default 3600
- `CACHE_MAX_ITEMS` - number, default 1000

**Критерии готовности:**
- Все типы корректно экспортируются из `config.types.ts`
- Конфигурация читается из переменных окружения
- Joi валидация работает при старте приложения
- TypeScript компилируется без ошибок типов

---

## Задача 3: Утилиты

### 3.1 SemVer утилита

**Файл:** `src/utils/semver.util.ts`

**Требования:**

1. **Класс `SemVerUtil` с статическими методами:**

2. **Метод `parse(version: string): SemVerComponents`:**
   - Парсит строку версии в формате "MAJOR.MINOR.PATCH"
   - Возвращает объект `{ major: number, minor: number, patch: number }`
   - Бросает ошибку для невалидного формата
   - Использовать regex: `/^(\d+)\.(\d+)\.(\d+)$/`

3. **Метод `compare(a: string, b: string): number`:**
   - Возвращает -1 если a < b, 0 если равны, 1 если a > b
   - Сравнивает по major, потом minor, потом patch

4. **Метод `isValidSemVer(version: string): boolean`:**
   - Проверяет валидность формата версии

5. **Метод `isAssetsCompatible(appVersion: string, candidateVersion: string): boolean`:**
   - Возвращает true если совпадает только MAJOR версия
   - Реализует правило совместимости assets из ТЗ

6. **Метод `isDefinitionsCompatible(appVersion: string, candidateVersion: string): boolean`:**
   - Возвращает true если совпадают MAJOR и MINOR версии
   - Реализует правило совместимости definitions из ТЗ

7. **Метод `pickBest(versions: string[]): string | null`:**
   - Возвращает самую новую версию из массива
   - null для пустого массива

8. **Метод `pickBestCompatible(appVersion: string, versions: string[], compatibilityFn: Function): string | null`:**
   - Фильтрует версии по функции совместимости
   - Возвращает лучшую совместимую версию

### 3.2 Структурированный логгер

**Файл:** `src/utils/logger.util.ts`

**Требования:**

1. **Класс `StructuredLogger` с статическими методами:**

2. **Управление Request ID:**
   - `setRequestId(requestId: string): void` - установить ID запроса
   - `clearRequestId(): void` - очистить ID запроса
   - Использовать приватное статическое поле для хранения

3. **Методы логирования:**
   - `info(event: string, context: Record<string, any> = {}): void`
   - `warn(event: string, context: Record<string, any> = {}): void`
   - `error(event: string, context: Record<string, any> = {}): void`
   - `debug(event: string, context: Record<string, any> = {}): void`

4. **Формат лог-записи (JSON):**
   ```typescript
   {
     ts: string,      // ISO timestamp
     event: string,
     ctx: object,
     rid?: string
   }
   ```

5. **Интеграция с NestJS Logger:**
   - Использовать `Logger` из `@nestjs/common`
   - Передавать форматированные JSON строки
   - **Все логи выводить в STDOUT** (для контейнеров)
   - В тестовой среде можно использовать STDERR чтобы не мешать PHPUnit

**Критерии готовности:**
- SemVer утилита проходит все тесты совместимости
- Логгер выводит структурированные JSON логи
- Все утилиты экспортируются корректно
- TypeScript типы корректны

---

## Задача 4: DTO и валидация

### 4.1 DTO для запроса конфигурации

**Файл:** `src/modules/config/dto/config-request.dto.ts`

**Требования:**

1. **Класс `ConfigRequestDto` с декораторами валидации:**

2. **Поле `appVersion`:**
   - Тип: `string`
   - Декораторы: `@IsString()`, `@Matches(/^\d+\.\d+\.\d+$/, { message: 'Invalid version format: appVersion' })`
   - Swagger: `@ApiProperty()` с описанием и примером

3. **Поле `platform`:**
   - Тип: `'android' | 'ios'`
   - Декораторы: `@IsString()`, `@IsIn(['android', 'ios'], { message: 'Invalid platform: $value' })`
   - Swagger: `@ApiProperty()` с enum

4. **Поле `assetsVersion` (опциональное):**
   - Тип: `string | undefined`
   - Декораторы: `@IsOptional()`, `@IsString()`, `@Matches()` с тем же regex
   - Swagger: `@ApiPropertyOptional()`

5. **Поле `definitionsVersion` (опциональное):**
   - Аналогично `assetsVersion`

6. **Настройки валидации:**
   - Все сообщения об ошибках должны точно соответствовать PHP версии
   - Использовать `transform: true` в ValidationPipe

**Критерии готовности:**
- DTO корректно валидирует все входные параметры
- Сообщения об ошибках соответствуют PHP версии
- Swagger документация генерируется автоматически
- TypeScript типы строгие

---

## Задача 5: Сервис фикстур

### 5.1 Сервис загрузки данных

**Файл:** `src/services/fixtures.service.ts`

**Требования:**

1. **Класс `FixturesService` с декоратором `@Injectable()`**

2. **Конструктор:**
   - Инжектить `ConfigService` из `@nestjs/config`

3. **Приватные поля:**
   - `cache: Map<string, { data: FixtureItem[], mtime: number, gen: number }>`
   - Кеш по ключу `"${kind}:${platform}"`

4. **Метод `loadFixtures(kind: 'assets' | 'definitions', platform: Platform): Promise<FixtureItem[]>`:**

   **Алгоритм:**
   - Получить путь к файлу через `getFixturePath(kind)`
   - Получить `mtime` файла через `fs.stat()`
   - Проверить локальный кеш по ключу и `mtime`
   - Если кеш актуален - вернуть из кеша с логом `fixtures_local_cache_hit`
   - Если кеш не актуален:
     - Прочитать файл через `fs.readFile()`
     - Распарсить JSON
     - Валидировать структуру (наличие `platform` ключа)
     - Обновить кеш с инкрементом `gen`
     - Залогировать `fixtures_loaded` или `fixtures_local_cache_invalidated`
   - При ошибках логировать `fixtures_read_failed` или `fixtures_json_invalid`

5. **Метод `toMap(fixtures: FixtureItem[]): Map<string, string>`:**
   - Преобразует массив в Map<version, hash>

6. **Метод `getGeneration(kind, platform): number`:**
   - Возвращает текущий generation для инвалидации кеша резолвера

7. **Метод `clearCache(kind, platform): void`:**
   - Очищает локальный кеш с логом `fixtures_local_cache_cleared`

8. **Приватный метод `getFixturePath(kind): string`:**
   - Получает абсолютный путь к файлу фикстур через ConfigService
   - Использовать `path.resolve()`

**Обработка ошибок:**
- При ошибке чтения файла - вернуть данные из старого кеша или пустой массив
- При ошибке парсинга JSON - аналогично
- Все ошибки логировать через StructuredLogger

**Критерии готовности:**
- Сервис корректно загружает JSON фикстуры
- Кеширование работает с инвалидацией по mtime
- Generation counter инкрементируется при обновлении
- Все ошибки обрабатываются gracefully
- Логирование соответствует PHP версии

---

## Задача 6: Сервис резолвера версий

### 6.1 Сервис разрешения совместимых версий

**Файл:** `src/services/resolver.service.ts`

**Требования:**

1. **Класс `ResolverService` с декоратором `@Injectable()`**

2. **Конструктор:**
   - Инжектить `FixturesService`
   - Инжектить `ConfigService`
   - Инжектить `CACHE_MANAGER` из `@nestjs/cache-manager`

3. **Приватные поля:**
   - `localCache: Map<string, ResolvedVersion>` для локального кеширования

4. **Интерфейс `ResolvedVersion`:**
   ```typescript
   interface ResolvedVersion {
     version: string;
     hash: string;
   }
   ```

5. **Метод `resolveAssets(appVersion: string, platform: Platform, explicitVersion?: string): Promise<ResolvedVersion | null>`:**

   **Алгоритм:**
   - Построить ключ кеша через `buildCacheKey('assets', platform, appVersion, explicitVersion)`
   - Проверить локальный кеш с логом `resolver_local_cache_hit`
   - Проверить внешний кеш (cache-manager) с логом `resolver_cache_hit`
   - Если кеш не помог:
     - Загрузить фикстуры через `fixturesService.loadFixtures('assets', platform)`
     - Преобразовать в Map через `fixturesService.toMap()`
     - **Если указана explicitVersion:**
       - Проверить существование в фикстурах
       - Проверить совместимость через `SemVerUtil.isAssetsCompatible()`
       - Если не совместима - залогировать `assets_explicit_not_found_or_incompatible` и вернуть null
     - **Если explicitVersion не указана:**
       - Найти лучшую совместимую версию через `SemVerUtil.pickBestCompatible()`
     - Если результат найден:
       - Сохранить в оба кеша
       - Залогировать `resolver_cache_set`
   - Вернуть результат или null

6. **Метод `resolveDefinitions()` - аналогично `resolveAssets()` но:**
   - Использовать `SemVerUtil.isDefinitionsCompatible()`
   - Логировать события с `kind: 'definitions'`

7. **Приватный метод `buildCacheKey(kind, platform, appVersion, explicitVersion?): string`:**
   - Включить generation фикстур в ключ для автоинвалидации
   - Формат: `"platform|appVersion|explicitVersion|A{genA}|D{genD}|kind"`
   - Использовать `fixturesService.getGeneration()` для получения gen

**Кеширование:**
- Локальный кеш - в памяти процесса
- Внешний кеш - через cache-manager (время жизни из конфига)
- Автоинвалидация через generation counter

**Критерии готовности:**
- Корректно разрешает совместимые версии по правилам SemVer
- Двухуровневое кеширование работает
- Автоинвалидация кеша при изменении фикстур
- Explicit версии обрабатываются корректно
- Логирование соответствует PHP версии

---

## Задача 7: Сервис конфигурации

### 7.1 Основной бизнес-сервис

**Файл:** `src/modules/config/config.service.ts`

**Требования:**

1. **Класс `AppConfigService` с декоратором `@Injectable()`**
   - Имя `AppConfigService` чтобы не конфликтовать с NestJS `ConfigService`

2. **Конструктор:**
   - Инжектить `ResolverService`
   - Инжектить `ConfigService` из `@nestjs/config`
   - Инжектить `CACHE_MANAGER`

3. **Метод `getConfig(appVersion: string, platform: Platform, assetsVersion?: string, definitionsVersion?: string): Promise<ConfigResponse | null>`:**

   **Алгоритм:**
   - Построить ключ кеша: `"config:${platform}:${appVersion}:${assetsVersion || ''}:${definitionsVersion || ''}"`
   - **Проверить кеш (только не в development режиме):**
     - Если найдено - залогировать `config_cache_hit` и вернуть
   - **Разрешить зависимости параллельно:**
     - `Promise.all()` для `resolverService.resolveAssets()` и `resolveDefinitions()`
   - **Если любая зависимость не найдена - вернуть null**
   - **Построить ответ:**
     ```typescript
     {
       backend_entry_point: { jsonrpc_url: config.get('urls.backendJsonRpc') },
       assets: {
         version: assets.version,
         hash: assets.hash,
         urls: config.get('urls.assets')
       },
       definitions: {
         version: definitions.version,
         hash: definitions.hash,
         urls: config.get('urls.definitions')
       },
       notifications: { jsonrpc_url: config.get('urls.notificationsJsonRpc') }
     }
     ```
   - **Сохранить в кеш (только не в development):**
     - Залогировать `config_cache_set`

**Особенности:**
- В development режиме кеш отключен для удобства разработки
- Используется `Promise.all()` для параллельного разрешения зависимостей
- Структура ответа точно соответствует PHP версии

**Критерии готовности:**
- Сервис корректно собирает финальную конфигурацию
- Кеширование работает (отключено в dev режиме)
- Параллельное разрешение зависимостей
- Структура ответа соответствует ТЗ

---

## Задача 8: Контроллер конфигурации

### 8.1 HTTP контроллер

**Файл:** `src/modules/config/config.controller.ts`

**Требования:**

1. **Класс `ConfigController` с декораторами:**
   - `@ApiTags('config')` для Swagger
   - `@Controller('config')` для маршрутизации

2. **Конструктор:**
   - Инжектить `AppConfigService`

3. **Метод `getConfig(@Query() query: ConfigRequestDto): Promise<ConfigResponse>`:**

   **Декораторы:**
   - `@Get()`
   - `@ApiOperation()` с описанием
   - `@ApiResponse()` для статусов 200, 400, 404, 500

   **Алгоритм:**
   - Деструктурировать параметры из `query`
   - Залогировать `config_request` с параметрами
   - **Try-catch блок:**
     - Вызвать `configService.getConfig()`
     - **Если результат null:**
       - Залогировать `config_not_found`
       - Бросить `HttpException` с статусом 404 и сообщением: `"Configuration not found for appVersion {appVersion} ({platform})"`
     - **Если результат найден:**
       - Залогировать `config_resolved` с версиями
       - Вернуть результат
   - **Catch блок:**
     - Если `HttpException` - пробросить дальше
     - Иначе залогировать `config_error` и бросить 500 ошибку

4. **Формат ошибок:**
   ```typescript
   {
     error: {
       code: number,
       message: string
     }
   }
   ```

**Swagger документация:**
- Подробные описания параметров
- Примеры запросов и ответов
- Все возможные коды ошибок

**Критерии готовности:**
- Контроллер корректно обрабатывает все типы запросов
- Валидация параметров через DTO
- Правильные HTTP статусы и сообщения об ошибках
- Swagger документация полная
- Логирование соответствует PHP версии

---

## Задача 9: Health check контроллер

### 9.1 Контроллер здоровья

**Файл:** `src/modules/health/health.controller.ts`

**Требования:**

1. **Класс `HealthController`:**
   - `@ApiTags('health')`
   - `@Controller('health')`

2. **Метод `getHealth(): { status: string }`:**
   - `@Get()`
   - `@ApiOperation()` и `@ApiResponse()`
   - Возвращать `{ status: 'ok' }`

**Файл:** `src/modules/health/health.module.ts`

**Требования:**
- Стандартный NestJS модуль с `HealthController`

**Критерии готовности:**
- Endpoint `/health` возвращает `{"status":"ok"}`
- Swagger документация присутствует

---

## Задача 10: Middleware и обработка ошибок

### 10.1 Middleware для логирования

**Файл:** `src/middleware/logging.middleware.ts`

**Требования:**

1. **Класс `LoggingMiddleware` реализующий `NestMiddleware`:**

2. **Метод `use(req: Request, res: Response, next: NextFunction): void`:**
   - Генерировать уникальный `requestId` через `uuid.v4()`
   - Установить `requestId` в `StructuredLogger.setRequestId()`
   - Добавить заголовок `X-Request-ID` в ответ
   - Залогировать `request_received` с данными запроса
   - **На событие `res.on('finish')`:**
     - Вычислить длительность запроса
     - Залогировать `response_sent` с метриками
     - Очистить `requestId` в логгере

### 10.2 Глобальный фильтр исключений

**Файл:** `src/exceptions/global-exception.filter.ts`

**Требования:**

1. **Класс `GlobalExceptionFilter` реализующий `ExceptionFilter`:**
   - Декоратор `@Catch()`

2. **Метод `catch(exception: unknown, host: ArgumentsHost): void`:**
   - Получить `response` и `request` из контекста
   - **Если `HttpException`:**
     - Получить статус и response
     - Если response уже в формате `{error: {...}}` - вернуть как есть
     - Иначе обернуть в формат ошибки
   - **Если не `HttpException`:**
     - Статус 500, сообщение "Internal server error"
   - Залогировать `unhandled_exception` с деталями
   - Вернуть JSON в формате `{error: {code, message}}`

**Критерии готовности:**
- Все запросы логируются с уникальным request ID
- Ошибки обрабатываются в едином формате
- Structured логи соответствуют PHP версии

---

## Задача 11: Модули и главное приложение

### 11.1 Модуль конфигурации

**Файл:** `src/modules/config/config.module.ts`

**Требования:**
```typescript
@Module({
  controllers: [ConfigController],
  providers: [AppConfigService, ResolverService, FixturesService],
})
export class ConfigModule {}
```

### 11.2 Главный модуль

**Файл:** `src/app.module.ts`

**Требования:**

1. **Декоратор `@Module()` с импортами:**
   - `ConfigModule.forRoot()` с глобальной конфигурацией
   - `CacheModule.register()` с глобальными настройками кеша
   - `ConfigModule` (наш модуль)
   - `HealthModule`

2. **Провайдеры:**
   - `APP_PIPE` с `ValidationPipe`
   - `APP_FILTER` с `GlobalExceptionFilter`

3. **Метод `configure(consumer: MiddlewareConsumer): void`:**
   - Применить `LoggingMiddleware` ко всем маршрутам

### 11.3 Точка входа

**Файл:** `src/main.ts`

**Требования:**

1. **Функция `bootstrap()`:**
   - Создать приложение через `NestFactory.create(AppModule)`
   - Настроить глобальный `ValidationPipe` с опциями:
     ```typescript
     {
       transform: true,
       whitelist: true,
       forbidNonWhitelisted: true,
     }
     ```
   - Настроить Swagger документацию на `/docs`
   - Запустить на порту из конфига
   - Залогировать `application_started`

**Критерии готовности:**
- Приложение запускается без ошибок
- Swagger UI доступен на `/docs`
- Все middleware и фильтры работают
- Валидация параметров функционирует

---

## Задача 12: Тестирование

### 12.1 Юнит-тесты для SemVer утилиты

**Файл:** `src/utils/__tests__/semver.util.spec.ts`

**Требования:**

1. **Тесты для `parse()`:**
   - Корректный парсинг валидных версий
   - Ошибка для невалидных версий

2. **Тесты для `compare()`:**
   - Сравнение версий всех комбинаций (-1, 0, 1)

3. **Тесты для `isAssetsCompatible()`:**
   - True для одинакового major
   - False для разного major
   - Проверка edge cases

4. **Тесты для `isDefinitionsCompatible()`:**
   - True для одинакового major.minor
   - False для разного minor или major

5. **Тесты для `pickBest()` и `pickBestCompatible()`:**
   - Выбор максимальной версии
   - Null для пустого массива
   - Фильтрация по совместимости

### 12.2 E2E тесты

**Файл:** `test/app.e2e-spec.ts`

**Требования:**

1. **Тест `/health`:**
   - Статус 200
   - Тело `{"status":"ok"}`

2. **Тест `/config` с валидными параметрами:**
   - Статус 200
   - Проверка структуры ответа (все ключи присутствуют)
   - Проверка типов данных

3. **Тесты ошибок:**
   - 400 для невалидной платформы
   - 400 для невалидного формата версии
   - 404 для несуществующей конфигурации

4. **Настройка тестов:**
   - Создание тестового модуля
   - Настройка ValidationPipe
   - Cleanup после тестов

**Критерии готовности:**
- Все юнит-тесты проходят
- E2E тесты покрывают основные сценарии
- Тесты запускаются через `npm test` и `npm run test:e2e`

---

## Задача 13: Docker и развертывание

### 13.1 Dockerfile

**Файл:** `Dockerfile`

**Требования:**

1. **Базовый образ:** `node:22-alpine`

2. **Этапы сборки:**
   - Установить рабочую директорию `/app`
   - Скопировать `package*.json`
   - Выполнить `npm ci --only=production`
   - Скопировать исходный код
   - Выполнить `npm run build`

3. **Безопасность:**
   - Создать пользователя `nodejs` с UID 1001
   - Переключиться на этого пользователя
   - Создать директорию `/app/data` с правильными правами

4. **Health check:**
   ```dockerfile
   HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
     CMD curl -f http://localhost:3000/health || exit 1
   ```

5. **Запуск:** `CMD ["node", "dist/main"]`

### 13.2 docker-compose.yml

**Файл:** `docker-compose.yml`

**Требования:**

1. **Сервис app:**
   - Сборка из текущей директории
   - Переменные окружения: `NODE_ENV=development`, `PORT=3000`, `LOG_LEVEL=info`
   - Порты: `3000:3000`
   - Volumes: `./data:/app/data:ro` (логи идут в STDOUT)
   - Health check встроен в Dockerfile

2. **Сеть:** `web` с драйвером bridge

### 13.3 package.json scripts

**Требования:**

Добавить/обновить скрипты:
```json
{
  "scripts": {
    "build": "nest build",
    "start": "nest start",
    "start:dev": "nest start --watch",
    "start:debug": "nest start --debug --watch",
    "start:prod": "node dist/main",
    "test": "jest",
    "test:watch": "jest --watch",
    "test:cov": "jest --coverage",
    "test:e2e": "jest --config ./test/jest-e2e.json"
  }
}
```

**Критерии готовности:**
- Docker образ собирается без ошибок
- Контейнер запускается и проходит health check
- Приложение доступно на порту 3000
- Логи пишутся в примонтированную директорию

---

## Задача 14: Финальная интеграция и тестирование

### 14.1 Создание тестовых данных

**Требования:**

1. **Создать директорию `data/` в корне проекта**

2. **Скопировать файлы фикстур из PHP проекта:**
   - `data/assets-fixtures.json`
   - `data/definitions-fixtures.json`

### 14.2 Интеграционное тестирование

**Требования:**

1. **Запустить приложение через Docker:**
   ```bash
   docker compose up -d --build
   ```

2. **Проверить endpoints:**
   ```bash
   # Health check
   curl http://localhost:3000/health
   # Ожидаемый ответ: {"status":"ok"}

   # Валидный запрос
   curl "http://localhost:3000/config?appVersion=14.1.100&platform=android"
   # Ожидаемый ответ: JSON с конфигурацией

   # Невалидная платформа
   curl "http://localhost:3000/config?appVersion=14.1.100&platform=desktop"
   # Ожидаемый ответ: 400 {"error":{"code":400,"message":"Invalid platform: desktop"}}

   # Невалидная версия
   curl "http://localhost:3000/config?appVersion=14.1&platform=android"
   # Ожидаемый ответ: 400 с сообщением о формате версии

   # Несуществующая конфигурация
   curl "http://localhost:3000/config?appVersion=99.99.99&platform=android"
   # Ожидаемый ответ: 404 с сообщением "Configuration not found"
   ```

3. **Проверить Swagger документацию:**
   - Открыть `http://localhost:3000/docs`
   - Убедиться что все endpoints документированы

4. **Проверить логи:**
   - Логи должны быть в JSON формате
   - Каждый запрос должен иметь уникальный request ID
   - События должны соответствовать PHP версии

### 14.3 Сравнение с PHP версией

**Требования:**

Убедиться что NestJS версия функционально эквивалентна PHP:

1. **Идентичные ответы для одинаковых запросов**
2. **Идентичные коды ошибок и сообщения**
3. **Аналогичная производительность**
4. **Схожая структура логов**

**Критерии готовности:**
- Все endpoints работают корректно
- Валидация параметров идентична PHP версии
- Swagger документация полная
- Docker контейнер стабильно работает
- Логирование структурированное и информативное

---

## Дополнительные требования

### Качество кода

1. **TypeScript:**
   - Строгие типы везде
   - Никаких `any` типов
   - Интерфейсы для всех объектов

2. **ESLint/Prettier:**
   - Настроить линтинг
   - Единообразное форматирование

3. **Документация:**
   - JSDoc комментарии для публичных методов
   - README с инструкциями по запуску

### Производительность

1. **Кеширование:**
   - Многоуровневое кеширование как в PHP версии
   - Автоинвалидация при изменении файлов

2. **Асинхронность:**
   - Все I/O операции асинхронные
   - Параллельное выполнение где возможно

### Мониторинг

1. **Структурированные логи:**
   - JSON формат
   - Корреляция через request ID
   - Все ключевые события

2. **Метрики:**
   - Время ответа
   - Количество запросов
   - Ошибки

---

## Критерии приемки

Проект считается завершенным когда:

1. ✅ Все 14 задач выполнены
2. ✅ Все тесты проходят (`npm test` и `npm run test:e2e`)
3. ✅ Docker контейнер запускается и работает стабильно
4. ✅ API функционально эквивалентен PHP версии
5. ✅ Swagger документация полная и корректная
6. ✅ Логирование структурированное и информативное
7. ✅ TypeScript компилируется без ошибок и предупреждений
8. ✅ Код соответствует стандартам NestJS

**Время выполнения:** 3-5 рабочих дней для middle разработчика, 5-8 дней для junior.

**Результат:** Полнофункциональный HTTP API сервис на NestJS, готовый к продакшн развертыванию.
