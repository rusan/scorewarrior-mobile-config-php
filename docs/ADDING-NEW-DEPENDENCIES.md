# Добавление новых типов зависимостей

Архитектура приложения позволяет легко добавлять новые типы зависимостей с SemVer версионированием. Каждый тип зависимости инкапсулирует свою логику совместимости.

## Шаги для добавления нового типа зависимости

### 1. Создайте класс типа зависимости

Создайте новый класс в `src/app/Services/DependencyTypes/`, реализующий `DependencyTypeInterface`:

```php
<?php
declare(strict_types=1);

namespace App\Services\DependencyTypes;

use App\Config\DataFileNames;
use App\Config\DependencyNames;
use App\Services\DependencyTypeInterface;
use App\Utils\Semver;

class YourNewType implements DependencyTypeInterface
{
    public function getName(): string
    {
        return 'your_new_type';
    }
    
    public function getFileName(): string
    {
        return 'your-new-type-fixtures.json';
    }
    
    public function getUrlsKey(): string
    {
        return 'your_new_type_cdn_urls';
    }
    
    public function isCompatible(string $appVersion, string $candidate): bool
    {
        $app = Semver::parse($appVersion);
        $cand = Semver::parse($candidate);
        

        
        return $app['major'] === $cand['major'];
    }
}
```

### 2. Зарегистрируйте тип в реестре

Добавьте регистрацию в `DependencyTypeRegistry::registerDefaultTypes()`:

```php
private function registerDefaultTypes(): void
{
    $this->register(new AssetsType());
    $this->register(new DefinitionsType());
    $this->register(new YourNewType());
}
```

**Примечание:** Реестр использует паттерн Singleton и зарегистрирован в DI контейнере, поэтому создается только один экземпляр на все приложение.

### 3. Добавьте поддержку URL

Добавьте метод в `UrlsService`:

```php
/** @return string[] */
public function getYourNewTypeUrls(): array
{
    return $this->getUrls()['your_new_type_cdn_urls'] ?? [];
}
```

### 4. Обновите ConfigInterface и BaseConfig

Добавьте метод в `ConfigInterface`:

```php
/** @return string[] */
public function getYourNewTypeUrls(): array;
```

Реализуйте в `BaseConfig`:

```php
/** @return string[] */
public function getYourNewTypeUrls(): array
{
    return $this->getUrlsService()->getYourNewTypeUrls();
}
```

### 5. Обновите ConfigService

Добавьте поддержку в `getUrlsForType()`:

```php
private function getUrlsForType(string $type): array
{
    return match($type) {
        'assets' => $this->config->getAssetsUrls(),
        'definitions' => $this->config->getDefinitionsUrls(),
        'your_new_type' => $this->config->getYourNewTypeUrls(),
        default => []
    };
}
```

### 6. Создайте файл фикстур

Создайте файл `data/your-new-type-fixtures.json`:

```json
{
  "android": [
    {
      "version": "14.1.100",
      "hash": "abc123def456..."
    },
    {
      "version": "14.2.200", 
      "hash": "def456ghi789..."
    }
  ],
  "ios": [
    {
      "version": "14.1.100",
      "hash": "xyz789uvw012..."
    }
  ]
}
```

### 7. Обновите urls-config.json

Добавьте URL для нового типа в `data/urls-config.json`:

```json
{
  "backend_jsonrpc_url": "https://api.application.com/jsonrpc/v2",
  "notifications_jsonrpc_url": "https://notifications.application.com/jsonrpc/v1",
  "assets_cdn_urls": ["https://dhm.cdn.application.com", "https://ehz.cdn.application.com"],
  "definitions_cdn_urls": ["https://fmp.cdn.application.com", "https://eau.cdn.application.com"],
  "your_new_type_cdn_urls": ["https://newtype.cdn.application.com", "https://newtype2.cdn.application.com"]
}
```

### 8. Обновите тесты

Добавьте тесты для нового типа зависимости в соответствующие тестовые классы.

### 9. Обновите документацию

Обновите README.md и другую документацию с примерами использования нового типа зависимости.

## Пример использования

После добавления нового типа зависимости, API будет автоматически поддерживать:

```bash
# Автоматический подбор версии
curl "http://127.0.0.1:8080/config?appVersion=14.1.100&platform=android"

# Явное указание версии
curl "http://127.0.0.1:8080/config?appVersion=14.1.100&platform=android&yourNewTypeVersion=14.2.200"
```

Ответ будет включать новый тип зависимости:

```json
{
  "backend_entry_point": {"jsonrpc_url": "..."},
  "assets": {"version": "...", "hash": "...", "urls": [...]},
  "definitions": {"version": "...", "hash": "...", "urls": [...]},
  "your_new_type": {"version": "...", "hash": "...", "urls": [...]},
  "notifications": {"jsonrpc_url": "..."}
}
```

## Преимущества новой архитектуры

- **Принцип единственной ответственности**: `Semver` отвечает только за работу с версиями, типы зависимостей - за свою логику совместимости
- **Отсутствие жесткой связанности**: `Semver` не знает о конкретных типах зависимостей
- **Расширяемость**: Для добавления нового типа не нужно модифицировать `Semver`
- **Open/Closed принцип**: Классы закрыты для модификации, но открыты для расширения
- **Инкапсуляция**: Логика совместимости инкапсулирована в соответствующих типах
- **Консистентность**: Все типы используют одинаковую логику резолвинга
- **Кэширование**: Автоматическое кэширование для всех типов
- **Логирование**: Структурированные логи для всех операций
- **Тестируемость**: Каждый тип можно тестировать независимо

## Стратегии совместимости

В методе `isCompatible()` вы можете реализовать различные стратегии совместимости:

- **MAJOR only**: `return $app['major'] === $cand['major'];` - совместимость только по мажорной версии
- **MAJOR.MINOR**: `return $app['major'] === $cand['major'] && $app['minor'] === $cand['minor'];` - совместимость по мажорной и минорной версии
- **Exact match**: `return $appVersion === $candidate;` - точное совпадение версий
- **Custom logic**: Любая другая логика совместимости, специфичная для вашего типа зависимости
