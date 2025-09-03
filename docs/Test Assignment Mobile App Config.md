# Test Assignment — Mobile App Config

## Разработка приложения

Разработать сервис, который будет отдавать конфигурацию клиенту, в зависимости от версии клиента и платформы. Сервис не предусматривает какой-либо авторизации и проверки прав доступа.

**Структура запроса**

```bash
GET /config?appVersion=13.6.956&platform=android
Accept: application/json
```

**Обязательные параметры**

- `appVersion`: `MAJOR.MINOR.PATCH` (все числа обязательны, SemVer)
- `platform`: `android` | `ios`

**Опциональные**

- `assetsVersion`: `MAJOR.MINOR.PATCH` (если задана — должна быть совместима, см. SemVer)
- `definitionsVersion`: `MAJOR.MINOR.PATCH` (если задана — должна быть совместима)

**Структура ответа**

```json
{
  "backend_entry_point": {
    "jsonrpc_url": "api.application.com/jsonrpc/v2"
  },
  "assets": {
    "version": "13.5.275",
		"hash": "0b313712189f60d9f46d36577140fb58beaec610353850f050cb8975f56ae381",
    "urls": [
      "dhm.cdn.application.com",
      "ehz.cdn.application.com"
    ]
  },
  "definitions": {
    "version": "13.6.610",
		"hash": "0d3606b99d782464b49dcf449c3c7e8551929abb1d7c00d9fec2ff522afd4f32",
    "urls": [
      "eau.cdn.application.com",
      "tbm.cdn.application.com"
    ]
  },
  "notifications": {
    "jsonrpc_url": "notifications.application.com/jsonrpc/v1"
  }
}
```

### Ошибки

- **400** — неверные параметры (формат версии, неподдерживаемая платформа и т.п.)
- **404** — подходящая конфигурация не найдена
- **500** — внутренняя ошибка

Примеры:

```json
{"error":
	{
		"code": 400,
		"message": "Invalid platform: desktop"
	}
}
```

```json
{
  "error": {
    "code": 404,
    "message": "Configuration not found for appVersion 13.6.956 (android)"
  }
}
```

### Расшифровка

- `assets`, `definitions` — необходимые данные для работы приложения имеют версию, хеш и список доменов, по которым их можно выкачать, могут быть привязаны работать только с определенной версией приложения
- `backend_entry_point`, `notifications` — **JsonRPC** сервисы для взаимодействия мобильного приложения, в структуре есть только актуальный домен с полным путем до сервиса

**Urls**

- `definitions_urls` и `assets_urls` — это массивы URL‑адресов до CDN/S3, по которым можно получить соответствующие ресурсы. На примере assets итоговый адрес клиент сформирует как `dhm.cdn.application.com/${hash}/assets.zip`
- `backend_entry_point.jsonrpc_url` и `notifications.jsonrpc_url` — это URL‑адреса соответствующих JsonRPC‑сервисов.

Эти URL **будут редко меняться** но **не должны быть зашиты в приложение**

### Содержимое / Данные

Данные `assets`, `definitions` и их `urls`. В архиве можно найти фикстуры этих данных в формате JSON. Urls нужно взять из примеров ниже.

### `assets`

Example values

```json
{"android":[{"version":"13.2.528","hash":"828e6360af99ad85332c23c613a772d7392b9d0fadb70529d808d71e3f9b3a2f"}]}
```

Urls

```json
{"assets_urls":["dhm.cdn.application.com","ehz.cdn.application.com"]}
```

### `definitions`

Example values

```json
{"android":[  {"version":"14.8.98","hash":"3cf8d7c4f083887f2212fc41d606c7f6951964fc57b4ccfcf87c5ea98d6e068a"},  {"version":"12.3.567","hash":"1100820bad8865c4c31341f4f8b45caddde600f7151e0830116a6ad5ad513706"}]}
```

Urls

```json
{"definitions_urls":["fmp.cdn.application.com","eau.cdn.application.com"]}
```

### SemVer

Есть `assets` и `definitions` разных версий, то есть для версии клиента есть своя версия assets. То же самое для `definitions` версия клиента есть версия `definitions`. 

Правила совместимости SemVer и  `appVersion`

- Все номера версий пишутся в формате MAJOR.MINOR.PATCH.
- `assets` и `appVersion`считаются совместимыми, если совпадает только первый компонент — MAJOR. Остальные два числа можно игнорировать.
- `definitions` и `appVersion`совместимы, когда совпадают два первых компонента — MAJOR и MINOR. Третий компонент (PATCH) роли не играет.
- Пример: клиент прислал `appVersion` 14.2.123. Тогда подойдут assets вида 14.x.y, а definitions вида 14.2.z.
- Если клиент явно указал `assetsVersion` или `definitionsVersion`, сервис возвращает именно эти версии — при условии, что они удовлетворяют правилам выше.
- Если подходящей версии нет, сервис отвечает ошибкой 404 с сообщением `Configuration not found for appVersion {appVersion} ({platform})`.

## Дополнительные требования

- Выполни на привычном тебе фреймворке
- Заложи возможность добавления зависимости с похожим SemVer версионированием: как у `assets` и `definitions`
- Используй структурированные логи в нужных местах
- Продумай кеширование и инвалидацию кеша при обновлении `assets` или `definitions`
- Чтобы было удобно смотреть оберни в Docker и добавь инструкцию по запуску
- Если у тебя будут вопросы, не стесняйся задать их

## Не прощаемся

Напиши, пожалуйста, сколько времени ты потратил на задание, а так же дай фидбек по нему.

**Спасибо!**