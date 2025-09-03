# Руководство по миграции Mobile Config API на NestJS

Это пошаговое руководство для создания аналогичного проекта на Node.js 22 + NestJS вместо PHP + Phalcon.

## Часть 1: Инициализация проекта

### 1.1 Создание базового проекта

```bash
# Создание нового NestJS проекта
npm i -g @nestjs/cli
nest new mobile-config-nestjs
cd mobile-config-nestjs

# Установка дополнительных зависимостей
npm install @nestjs/config @nestjs/cache-manager cache-manager
npm install class-validator class-transformer
npm install @nestjs/swagger swagger-ui-express

# Dev зависимости
npm install --save-dev @types/node @types/jest supertest
```

### 1.2 Структура проекта

```text
src/
├── app.module.ts
├── main.ts
├── config/
│   ├── configuration.ts
│   └── validation.schema.ts
├── modules/
│   ├── config/
│   │   ├── config.controller.ts
│   │   ├── config.service.ts
│   │   ├── config.module.ts
│   │   └── dto/
│   │       └── config-request.dto.ts
│   └── health/
│       ├── health.controller.ts
│       └── health.module.ts
├── services/
│   ├── fixtures.service.ts
│   ├── resolver.service.ts
│   ├── cache.service.ts
│   └── urls.service.ts
├── utils/
│   ├── semver.util.ts
│   ├── logger.util.ts
│   └── http.util.ts
├── middleware/
│   ├── logging.middleware.ts
│   └── validation.middleware.ts
├── exceptions/
│   ├── validation.exception.ts
│   └── config-not-found.exception.ts
└── types/
    └── config.types.ts
```

## Часть 2: Конфигурация и типы

### 2.0 Логика формирования URL

**Важно:** URLs в ответе API - это базовые адреса CDN/S3, а не готовые ссылки на файлы.

- `assets.urls` и `definitions.urls` - массивы базовых URL до CDN
- Клиент формирует финальные URL: `${baseUrl}/${hash}/assets.zip` или `${baseUrl}/${hash}/definitions.zip`
- `backend_entry_point.jsonrpc_url` и `notifications.jsonrpc_url` - готовые URL JsonRPC сервисов

**Пример:**
```json
{
  "assets": {
    "version": "14.3.688",
    "hash": "abc123def456",
    "urls": ["dhm.cdn.application.com", "ehz.cdn.application.com"]
  }
}
```

Клиент сформирует URL: `dhm.cdn.application.com/abc123def456/assets.zip`

### 2.1 Основные типы (src/types/config.types.ts)

```typescript
export interface FixtureItem {
  version: string;
  hash: string;
}

export interface PlatformFixtures {
  android: FixtureItem[];
  ios: FixtureItem[];
}

export interface ConfigResponse {
  backend_entry_point: {
    jsonrpc_url: string;
  };
  assets: {
    version: string;
    hash: string;
    urls: string[]; // Base CDN URLs, client forms: ${url}/${hash}/assets.zip
  };
  definitions: {
    version: string;
    hash: string;
    urls: string[]; // Base CDN URLs, client forms: ${url}/${hash}/definitions.zip
  };
  notifications: {
    jsonrpc_url: string;
  };
}

export interface ErrorResponse {
  error: {
    code: number;
    message: string;
  };
}

export type Platform = 'android' | 'ios';
```

### 2.2 Конфигурация приложения (src/config/configuration.ts)

```typescript
export default () => ({
  port: parseInt(process.env.PORT, 10) || 3000,
  nodeEnv: process.env.NODE_ENV || 'development',
  logLevel: process.env.LOG_LEVEL || 'info',
  
  urls: {
    // URLs should come from external sources (DB, API, env vars)
    // NOT hardcoded in application code!
    backendJsonRpc: process.env.BACKEND_JSONRPC_URL || '',
    notificationsJsonRpc: process.env.NOTIFICATIONS_JSONRPC_URL || '',
    // Base CDN URLs - loaded dynamically from external source
    assets: process.env.ASSETS_CDN_URLS ? process.env.ASSETS_CDN_URLS.split(',') : [],
    definitions: process.env.DEFINITIONS_CDN_URLS ? process.env.DEFINITIONS_CDN_URLS.split(',') : []
  },
  
  fixtures: {
    assetsPath: process.env.ASSETS_FIXTURES_PATH || './data/assets-fixtures.json',
    definitionsPath: process.env.DEFINITIONS_FIXTURES_PATH || './data/definitions-fixtures.json'
  },
  
  cache: {
    ttl: parseInt(process.env.CACHE_TTL, 10) || 3600,
    max: parseInt(process.env.CACHE_MAX_ITEMS, 10) || 1000
  }
});
```

### 2.3 Валидация конфигурации (src/config/validation.schema.ts)

```typescript
import * as Joi from 'joi';

export const validationSchema = Joi.object({
  NODE_ENV: Joi.string()
    .valid('development', 'production', 'test')
    .default('development'),
  PORT: Joi.number().default(3000),
  LOG_LEVEL: Joi.string()
    .valid('error', 'warn', 'info', 'debug')
    .default('info'),
  CACHE_TTL: Joi.number().default(3600),
  CACHE_MAX_ITEMS: Joi.number().default(1000),
});
```

## Часть 3: Утилиты

### 3.1 SemVer утилита (src/utils/semver.util.ts)

```typescript
export interface SemVerComponents {
  major: number;
  minor: number;
  patch: number;
}

export class SemVerUtil {
  private static readonly SEMVER_REGEX = /^(\d+)\.(\d+)\.(\d+)$/;

  static parse(version: string): SemVerComponents {
    const match = version.match(this.SEMVER_REGEX);
    if (!match) {
      throw new Error(`Invalid SemVer: ${version}`);
    }

    return {
      major: parseInt(match[1], 10),
      minor: parseInt(match[2], 10),
      patch: parseInt(match[3], 10),
    };
  }

  static compare(a: string, b: string): number {
    const versionA = this.parse(a);
    const versionB = this.parse(b);

    if (versionA.major !== versionB.major) {
      return versionA.major - versionB.major;
    }
    if (versionA.minor !== versionB.minor) {
      return versionA.minor - versionB.minor;
    }
    return versionA.patch - versionB.patch;
  }

  static isValidSemVer(version: string): boolean {
    return this.SEMVER_REGEX.test(version);
  }

  static isAssetsCompatible(appVersion: string, candidateVersion: string): boolean {
    const app = this.parse(appVersion);
    const candidate = this.parse(candidateVersion);
    return app.major === candidate.major;
  }

  static isDefinitionsCompatible(appVersion: string, candidateVersion: string): boolean {
    const app = this.parse(appVersion);
    const candidate = this.parse(candidateVersion);
    return app.major === candidate.major && app.minor === candidate.minor;
  }

  static pickBest(versions: string[]): string | null {
    if (versions.length === 0) return null;
    return versions.sort((a, b) => -this.compare(a, b))[0];
  }

  static pickBestCompatible(
    appVersion: string,
    versions: string[],
    compatibilityFn: (app: string, candidate: string) => boolean
  ): string | null {
    const compatible = versions.filter(v => compatibilityFn(appVersion, v));
    return this.pickBest(compatible);
  }
}
```

### 3.2 Логгер (src/utils/logger.util.ts)

```typescript
import { Logger as NestLogger } from '@nestjs/common';

export class StructuredLogger {
  private static logger = new NestLogger('MobileConfig');
  private static requestId: string | null = null;

  static setRequestId(requestId: string): void {
    this.requestId = requestId;
  }

  static clearRequestId(): void {
    this.requestId = null;
  }

  private static formatMessage(event: string, context: Record<string, any> = {}): string {
    const logEntry = {
      ts: new Date().toISOString(),
      event,
      ctx: context,
      ...(this.requestId && { rid: this.requestId })
    };
    return JSON.stringify(logEntry);
  }

  static info(event: string, context: Record<string, any> = {}): void {
    this.logger.log(this.formatMessage(event, context));
  }

  static warn(event: string, context: Record<string, any> = {}): void {
    this.logger.warn(this.formatMessage(event, context));
  }

  static error(event: string, context: Record<string, any> = {}): void {
    this.logger.error(this.formatMessage(event, context));
  }

  static debug(event: string, context: Record<string, any> = {}): void {
    this.logger.debug(this.formatMessage(event, context));
  }
}
```

## Часть 4: DTO и валидация

### 4.1 DTO для запроса конфигурации (src/modules/config/dto/config-request.dto.ts)

```typescript
import { IsString, IsOptional, Matches, IsIn } from 'class-validator';
import { Transform } from 'class-transformer';
import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';

export class ConfigRequestDto {
  @ApiProperty({
    description: 'Application version in MAJOR.MINOR.PATCH format',
    example: '14.1.100',
    pattern: '^\\d+\\.\\d+\\.\\d+$'
  })
  @IsString()
  @Matches(/^\d+\.\d+\.\d+$/, {
    message: 'Invalid version format: appVersion'
  })
  appVersion: string;

  @ApiProperty({
    description: 'Platform type',
    enum: ['android', 'ios'],
    example: 'android'
  })
  @IsString()
  @IsIn(['android', 'ios'], {
    message: 'Invalid platform: $value'
  })
  platform: 'android' | 'ios';

  @ApiPropertyOptional({
    description: 'Optional assets version in MAJOR.MINOR.PATCH format',
    example: '14.3.688',
    pattern: '^\\d+\\.\\d+\\.\\d+$'
  })
  @IsOptional()
  @IsString()
  @Matches(/^\d+\.\d+\.\d+$/, {
    message: 'Invalid version format: assetsVersion'
  })
  assetsVersion?: string;

  @ApiPropertyOptional({
    description: 'Optional definitions version in MAJOR.MINOR.PATCH format',
    example: '14.1.487',
    pattern: '^\\d+\\.\\d+\\.\\d+$'
  })
  @IsOptional()
  @IsString()
  @Matches(/^\d+\.\d+\.\d+$/, {
    message: 'Invalid version format: definitionsVersion'
  })
  definitionsVersion?: string;
}
```

## Часть 5: Сервисы

### 5.1 Сервис фикстур (src/services/fixtures.service.ts)

```typescript
import { Injectable, Logger } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import * as fs from 'fs/promises';
import * as path from 'path';
import { FixtureItem, PlatformFixtures, Platform } from '../types/config.types';
import { StructuredLogger } from '../utils/logger.util';

@Injectable()
export class FixturesService {
  private readonly logger = new Logger(FixturesService.name);
  private readonly cache = new Map<string, { data: FixtureItem[]; mtime: number; gen: number }>();

  constructor(private readonly configService: ConfigService) {}

  async loadFixtures(kind: 'assets' | 'definitions', platform: Platform): Promise<FixtureItem[]> {
    const key = `${kind}:${platform}`;
    const filePath = this.getFixturePath(kind);
    
    try {
      const stats = await fs.stat(filePath);
      const mtime = stats.mtimeMs;
      
      // Check cache
      const cached = this.cache.get(key);
      if (cached && cached.mtime === mtime) {
        StructuredLogger.info('fixtures_local_cache_hit', { kind, platform, mtime });
        return cached.data;
      }

      // Load from file
      const rawData = await fs.readFile(filePath, 'utf-8');
      const jsonData: PlatformFixtures = JSON.parse(rawData);
      
      if (!jsonData[platform] || !Array.isArray(jsonData[platform])) {
        StructuredLogger.error('fixtures_json_invalid', { kind, platform, filePath });
        return cached?.data || [];
      }

      const data = jsonData[platform];
      const gen = (cached?.gen || 0) + 1;
      
      this.cache.set(key, { data, mtime, gen });
      
      if (cached) {
        StructuredLogger.info('fixtures_local_cache_invalidated', { kind, platform, mtime, gen });
      } else {
        StructuredLogger.info('fixtures_loaded', { kind, platform, mtime, gen });
      }
      
      return data;
    } catch (error) {
      StructuredLogger.error('fixtures_read_failed', { kind, platform, filePath, error: error.message });
      return this.cache.get(key)?.data || [];
    }
  }

  toMap(fixtures: FixtureItem[]): Map<string, string> {
    const map = new Map<string, string>();
    fixtures.forEach(item => {
      map.set(item.version, item.hash);
    });
    return map;
  }

  getGeneration(kind: 'assets' | 'definitions', platform: Platform): number {
    const key = `${kind}:${platform}`;
    return this.cache.get(key)?.gen || 0;
  }

  clearCache(kind: 'assets' | 'definitions', platform: Platform): void {
    const key = `${kind}:${platform}`;
    this.cache.delete(key);
    StructuredLogger.info('fixtures_local_cache_cleared', { kind, platform });
  }

  private getFixturePath(kind: 'assets' | 'definitions'): string {
    const basePath = kind === 'assets' 
      ? this.configService.get<string>('fixtures.assetsPath')
      : this.configService.get<string>('fixtures.definitionsPath');
    
    return path.resolve(basePath);
  }
}
```

### 5.2 Сервис URLs (src/services/urls.service.ts)

```typescript
import { Injectable, Logger } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { CACHE_MANAGER } from '@nestjs/cache-manager';
import { Cache } from 'cache-manager';
import { StructuredLogger } from '../utils/logger.util';

interface UrlsConfig {
  backendJsonRpc: string;
  notificationsJsonRpc: string;
  assets: string[];
  definitions: string[];
}

@Injectable()
export class UrlsService {
  private readonly logger = new Logger(UrlsService.name);
  private readonly cache = new Map<string, UrlsConfig>();

  constructor(
    private readonly configService: ConfigService,
    @Inject(CACHE_MANAGER) private readonly cacheManager: Cache,
  ) {}

  async getUrls(): Promise<UrlsConfig> {
    const cacheKey = 'urls_config';
    
    // Check local cache first
    if (this.cache.has(cacheKey)) {
      StructuredLogger.info('urls_local_cache_hit');
      return this.cache.get(cacheKey);
    }

    // Check external cache
    const cached = await this.cacheManager.get<UrlsConfig>(cacheKey);
    if (cached) {
      StructuredLogger.info('urls_cache_hit');
      this.cache.set(cacheKey, cached);
      return cached;
    }

    // Load URLs from external source (DB, API, etc.)
    const urls = await this.loadUrlsFromExternalSource();
    
    // Cache the result
    this.cache.set(cacheKey, urls);
    await this.cacheManager.set(cacheKey, urls, 3600); // 1 hour TTL
    
    StructuredLogger.info('urls_loaded', { 
      backend: urls.backendJsonRpc,
      notifications: urls.notificationsJsonRpc,
      assetsCount: urls.assets.length,
      definitionsCount: urls.definitions.length
    });

    return urls;
  }

  private async loadUrlsFromExternalSource(): Promise<UrlsConfig> {
    // TODO: Implement actual external source loading
    // This could be:
    // 1. Database query
    // 2. External API call
    // 3. Configuration service
    // 4. Environment variables (fallback)
    
    // For now, fallback to environment variables
    return {
      backendJsonRpc: this.configService.get('urls.backendJsonRpc'),
      notificationsJsonRpc: this.configService.get('urls.notificationsJsonRpc'),
      assets: this.configService.get('urls.assets'),
      definitions: this.configService.get('urls.definitions')
    };
  }

  clearCache(): void {
    this.cache.clear();
    StructuredLogger.info('urls_cache_cleared');
  }
}
```

### 5.3 Сервис резолвера (src/services/resolver.service.ts)

```typescript
import { Injectable, Inject } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { CACHE_MANAGER } from '@nestjs/cache-manager';
import { Cache } from 'cache-manager';
import { FixturesService } from './fixtures.service';
import { SemVerUtil } from '../utils/semver.util';
import { StructuredLogger } from '../utils/logger.util';
import { Platform } from '../types/config.types';

interface ResolvedVersion {
  version: string;
  hash: string;
}

@Injectable()
export class ResolverService {
  private readonly localCache = new Map<string, ResolvedVersion>();

  constructor(
    private readonly fixturesService: FixturesService,
    private readonly configService: ConfigService,
    @Inject(CACHE_MANAGER) private readonly cacheManager: Cache,
  ) {}

  async resolveAssets(
    appVersion: string,
    platform: Platform,
    explicitVersion?: string
  ): Promise<ResolvedVersion | null> {
    const cacheKey = this.buildCacheKey('assets', platform, appVersion, explicitVersion);
    
    // Check local cache
    if (this.localCache.has(cacheKey)) {
      StructuredLogger.info('resolver_local_cache_hit', { kind: 'assets', key: cacheKey });
      return this.localCache.get(cacheKey);
    }

    // Check external cache
    const cached = await this.cacheManager.get<ResolvedVersion>(cacheKey);
    if (cached) {
      StructuredLogger.info('resolver_cache_hit', { kind: 'assets', key: cacheKey });
      this.localCache.set(cacheKey, cached);
      return cached;
    }

    // Load fixtures and resolve
    const fixtures = await this.fixturesService.loadFixtures('assets', platform);
    const fixtureMap = this.fixturesService.toMap(fixtures);
    const versions = Array.from(fixtureMap.keys());

    let result: ResolvedVersion | null = null;

    if (explicitVersion) {
      if (fixtureMap.has(explicitVersion) && SemVerUtil.isAssetsCompatible(appVersion, explicitVersion)) {
        result = {
          version: explicitVersion,
          hash: fixtureMap.get(explicitVersion)
        };
      } else {
        StructuredLogger.info('assets_explicit_not_found_or_incompatible', {
          platform, appVersion, assetsVersion: explicitVersion
        });
        return null;
      }
    } else {
      const bestVersion = SemVerUtil.pickBestCompatible(
        appVersion,
        versions,
        SemVerUtil.isAssetsCompatible
      );

      if (bestVersion) {
        result = {
          version: bestVersion,
          hash: fixtureMap.get(bestVersion)
        };
      }
    }

    if (result) {
      // Cache the result
      this.localCache.set(cacheKey, result);
      await this.cacheManager.set(cacheKey, result);
      
      StructuredLogger.info('resolver_cache_set', {
        kind: 'assets',
        key: cacheKey,
        version: result.version
      });
    }

    return result;
  }

  async resolveDefinitions(
    appVersion: string,
    platform: Platform,
    explicitVersion?: string
  ): Promise<ResolvedVersion | null> {
    const cacheKey = this.buildCacheKey('definitions', platform, appVersion, explicitVersion);
    
    // Check local cache
    if (this.localCache.has(cacheKey)) {
      StructuredLogger.info('resolver_local_cache_hit', { kind: 'definitions', key: cacheKey });
      return this.localCache.get(cacheKey);
    }

    // Check external cache
    const cached = await this.cacheManager.get<ResolvedVersion>(cacheKey);
    if (cached) {
      StructuredLogger.info('resolver_cache_hit', { kind: 'definitions', key: cacheKey });
      this.localCache.set(cacheKey, cached);
      return cached;
    }

    // Load fixtures and resolve
    const fixtures = await this.fixturesService.loadFixtures('definitions', platform);
    const fixtureMap = this.fixturesService.toMap(fixtures);
    const versions = Array.from(fixtureMap.keys());

    let result: ResolvedVersion | null = null;

    if (explicitVersion) {
      if (fixtureMap.has(explicitVersion) && SemVerUtil.isDefinitionsCompatible(appVersion, explicitVersion)) {
        result = {
          version: explicitVersion,
          hash: fixtureMap.get(explicitVersion)
        };
      } else {
        StructuredLogger.info('definitions_explicit_not_found_or_incompatible', {
          platform, appVersion, definitionsVersion: explicitVersion
        });
        return null;
      }
    } else {
      const bestVersion = SemVerUtil.pickBestCompatible(
        appVersion,
        versions,
        SemVerUtil.isDefinitionsCompatible
      );

      if (bestVersion) {
        result = {
          version: bestVersion,
          hash: fixtureMap.get(bestVersion)
        };
      }
    }

    if (result) {
      // Cache the result
      this.localCache.set(cacheKey, result);
      await this.cacheManager.set(cacheKey, result);
      
      StructuredLogger.info('resolver_cache_set', {
        kind: 'definitions',
        key: cacheKey,
        version: result.version
      });
    }

    return result;
  }

  private buildCacheKey(
    kind: 'assets' | 'definitions',
    platform: Platform,
    appVersion: string,
    explicitVersion?: string
  ): string {
    const genA = this.fixturesService.getGeneration('assets', platform);
    const genD = this.fixturesService.getGeneration('definitions', platform);
    
    return [
      platform,
      appVersion,
      explicitVersion || '-',
      `A${genA}`,
      `D${genD}`,
      kind
    ].join('|');
  }
}
```

## Часть 6: Контроллеры и модули

### 6.1 Контроллер конфигурации (src/modules/config/config.controller.ts)

```typescript
import { Controller, Get, Query, HttpException, HttpStatus } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiQuery } from '@nestjs/swagger';
import { ConfigService as AppConfigService } from './config.service';
import { ConfigRequestDto } from './dto/config-request.dto';
import { ConfigResponse, ErrorResponse } from '../../types/config.types';
import { StructuredLogger } from '../../utils/logger.util';

@ApiTags('config')
@Controller('config')
export class ConfigController {
  constructor(private readonly configService: AppConfigService) {}

  @Get()
  @ApiOperation({ 
    summary: 'Get mobile app configuration',
    description: 'Returns configuration for mobile app based on version and platform'
  })
  @ApiResponse({ 
    status: 200, 
    description: 'Configuration found and returned',
    type: 'object'
  })
  @ApiResponse({ 
    status: 400, 
    description: 'Invalid request parameters',
    schema: {
      example: {
        error: {
          code: 400,
          message: 'Invalid platform: desktop'
        }
      }
    }
  })
  @ApiResponse({ 
    status: 404, 
    description: 'Configuration not found',
    schema: {
      example: {
        error: {
          code: 404,
          message: 'Configuration not found for appVersion 14.1.100 (android)'
        }
      }
    }
  })
  async getConfig(@Query() query: ConfigRequestDto): Promise<ConfigResponse> {
    const { appVersion, platform, assetsVersion, definitionsVersion } = query;
    
    StructuredLogger.info('config_request', {
      platform,
      appVer: appVersion,
      assetsVer: assetsVersion,
      defsVer: definitionsVersion
    });

    try {
      const result = await this.configService.getConfig(
        appVersion,
        platform,
        assetsVersion,
        definitionsVersion
      );

      if (!result) {
        StructuredLogger.info('config_not_found', {
          platform,
          appVer: appVersion,
          assetsVer: assetsVersion,
          defsVer: definitionsVersion
        });
        
        throw new HttpException(
          {
            error: {
              code: 404,
              message: `Configuration not found for appVersion ${appVersion} (${platform})`
            }
          },
          HttpStatus.NOT_FOUND
        );
      }

      StructuredLogger.info('config_resolved', {
        platform,
        appVersion,
        assetsVersion: result.assets.version,
        definitionsVersion: result.definitions.version
      });

      return result;
    } catch (error) {
      if (error instanceof HttpException) {
        throw error;
      }

      StructuredLogger.error('config_error', {
        message: error.message,
        platform,
        appVer: appVersion
      });

      throw new HttpException(
        {
          error: {
            code: 500,
            message: 'Internal server error'
          }
        },
        HttpStatus.INTERNAL_SERVER_ERROR
      );
    }
  }
}
```

### 6.2 Сервис конфигурации (src/modules/config/config.service.ts)

```typescript
import { Injectable, Inject } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { CACHE_MANAGER } from '@nestjs/cache-manager';
import { Cache } from 'cache-manager';
import { ResolverService } from '../../services/resolver.service';
import { UrlsService } from '../../services/urls.service';
import { ConfigResponse, Platform } from '../../types/config.types';
import { StructuredLogger } from '../../utils/logger.util';

@Injectable()
export class AppConfigService {
  constructor(
    private readonly resolverService: ResolverService,
    private readonly urlsService: UrlsService,
    private readonly configService: ConfigService,
    @Inject(CACHE_MANAGER) private readonly cacheManager: Cache,
  ) {}

  async getConfig(
    appVersion: string,
    platform: Platform,
    assetsVersion?: string,
    definitionsVersion?: string
  ): Promise<ConfigResponse | null> {
    const cacheKey = `config:${platform}:${appVersion}:${assetsVersion || ''}:${definitionsVersion || ''}`;
    
    // Check cache first (skip in development)
    const isDev = this.configService.get('nodeEnv') === 'development';
    if (!isDev) {
      const cachedResult = await this.cacheManager.get<ConfigResponse>(cacheKey);
      if (cachedResult) {
        StructuredLogger.info('config_cache_hit', { key: cacheKey });
        return cachedResult;
      }
    }

    // Load URLs dynamically (not hardcoded!)
    const urls = await this.urlsService.getUrls();

    // Resolve assets and definitions
    const [assets, definitions] = await Promise.all([
      this.resolverService.resolveAssets(appVersion, platform, assetsVersion),
      this.resolverService.resolveDefinitions(appVersion, platform, definitionsVersion)
    ]);

    if (!assets || !definitions) {
      return null;
    }

    const result: ConfigResponse = {
      backend_entry_point: {
        jsonrpc_url: urls.backendJsonRpc
      },
      assets: {
        version: assets.version,
        hash: assets.hash,
        // Base CDN URLs - client forms: ${url}/${hash}/assets.zip
        urls: urls.assets
      },
      definitions: {
        version: definitions.version,
        hash: definitions.hash,
        // Base CDN URLs - client forms: ${url}/${hash}/definitions.zip
        urls: urls.definitions
      },
      notifications: {
        jsonrpc_url: urls.notificationsJsonRpc
      }
    };

    // Cache the result (skip in development)
    if (!isDev) {
      await this.cacheManager.set(cacheKey, result);
      StructuredLogger.info('config_cache_set', { key: cacheKey });
    }

    return result;
  }
}
```

## Часть 7: Middleware и исключения

### 7.1 Middleware для логирования (src/middleware/logging.middleware.ts)

```typescript
import { Injectable, NestMiddleware } from '@nestjs/common';
import { Request, Response, NextFunction } from 'express';
import { StructuredLogger } from '../utils/logger.util';
import { v4 as uuidv4 } from 'uuid';

@Injectable()
export class LoggingMiddleware implements NestMiddleware {
  use(req: Request, res: Response, next: NextFunction): void {
    const requestId = uuidv4();
    const startTime = Date.now();
    
    // Set request ID for structured logging
    StructuredLogger.setRequestId(requestId);
    
    // Add request ID to response headers for debugging
    res.setHeader('X-Request-ID', requestId);
    
    StructuredLogger.info('request_received', {
      method: req.method,
      url: req.url,
      userAgent: req.get('User-Agent'),
      ip: req.ip
    });

    res.on('finish', () => {
      const duration = Date.now() - startTime;
      
      StructuredLogger.info('response_sent', {
        method: req.method,
        url: req.url,
        statusCode: res.statusCode,
        duration
      });
      
      // Clear request ID
      StructuredLogger.clearRequestId();
    });

    next();
  }
}
```

### 7.2 Глобальный фильтр исключений (src/exceptions/global-exception.filter.ts)

```typescript
import { ExceptionFilter, Catch, ArgumentsHost, HttpException, HttpStatus } from '@nestjs/common';
import { Request, Response } from 'express';
import { StructuredLogger } from '../utils/logger.util';

@Catch()
export class GlobalExceptionFilter implements ExceptionFilter {
  catch(exception: unknown, host: ArgumentsHost): void {
    const ctx = host.switchToHttp();
    const response = ctx.getResponse<Response>();
    const request = ctx.getRequest<Request>();

    let status = HttpStatus.INTERNAL_SERVER_ERROR;
    let message = 'Internal server error';

    if (exception instanceof HttpException) {
      status = exception.getStatus();
      const exceptionResponse = exception.getResponse();
      
      if (typeof exceptionResponse === 'object' && 'error' in exceptionResponse) {
        // Already formatted error response
        response.status(status).json(exceptionResponse);
        return;
      }
      
      message = exception.message;
    }

    const errorResponse = {
      error: {
        code: status,
        message
      }
    };

    StructuredLogger.error('unhandled_exception', {
      method: request.method,
      url: request.url,
      statusCode: status,
      message,
      stack: exception instanceof Error ? exception.stack : undefined
    });

    response.status(status).json(errorResponse);
  }
}
```

## Часть 8: Модули и главное приложение

### 8.1 Модуль конфигурации (src/modules/config/config.module.ts)

```typescript
import { Module } from '@nestjs/common';
import { ConfigController } from './config.controller';
import { AppConfigService } from './config.service';
import { ResolverService } from '../../services/resolver.service';
import { FixturesService } from '../../services/fixtures.service';
import { UrlsService } from '../../services/urls.service';

@Module({
  controllers: [ConfigController],
  providers: [AppConfigService, ResolverService, FixturesService, UrlsService],
})
export class ConfigModule {}
```

### 8.2 Модуль health check (src/modules/health/health.module.ts)

```typescript
import { Module } from '@nestjs/common';
import { HealthController } from './health.controller';

@Module({
  controllers: [HealthController],
})
export class HealthModule {}
```

### 8.3 Health контроллер (src/modules/health/health.controller.ts)

```typescript
import { Controller, Get } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse } from '@nestjs/swagger';

@ApiTags('health')
@Controller('health')
export class HealthController {
  @Get()
  @ApiOperation({ summary: 'Health check endpoint' })
  @ApiResponse({ 
    status: 200, 
    description: 'Service is healthy',
    schema: {
      example: { status: 'ok' }
    }
  })
  getHealth(): { status: string } {
    return { status: 'ok' };
  }
}
```

### 8.4 Главный модуль приложения (src/app.module.ts)

```typescript
import { Module, MiddlewareConsumer, ValidationPipe } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { CacheModule } from '@nestjs/cache-manager';
import { APP_PIPE, APP_FILTER } from '@nestjs/core';
import configuration from './config/configuration';
import { validationSchema } from './config/validation.schema';
import { ConfigModule as AppConfigModule } from './modules/config/config.module';
import { HealthModule } from './modules/health/health.module';
import { LoggingMiddleware } from './middleware/logging.middleware';
import { GlobalExceptionFilter } from './exceptions/global-exception.filter';

@Module({
  imports: [
    ConfigModule.forRoot({
      isGlobal: true,
      load: [configuration],
      validationSchema,
    }),
    CacheModule.register({
      isGlobal: true,
      ttl: 3600, // 1 hour
      max: 1000, // maximum number of items in cache
    }),
    AppConfigModule,
    HealthModule,
  ],
  providers: [
    {
      provide: APP_PIPE,
      useClass: ValidationPipe,
    },
    {
      provide: APP_FILTER,
      useClass: GlobalExceptionFilter,
    },
  ],
})
export class AppModule {
  configure(consumer: MiddlewareConsumer): void {
    consumer.apply(LoggingMiddleware).forRoutes('*');
  }
}
```

### 8.5 Точка входа (src/main.ts)

```typescript
import { NestFactory } from '@nestjs/core';
import { ValidationPipe } from '@nestjs/common';
import { SwaggerModule, DocumentBuilder } from '@nestjs/swagger';
import { AppModule } from './app.module';
import { StructuredLogger } from './utils/logger.util';

async function bootstrap() {
  const app = await NestFactory.create(AppModule);

  // Global validation pipe
  app.useGlobalPipes(new ValidationPipe({
    transform: true,
    whitelist: true,
    forbidNonWhitelisted: true,
  }));

  // Swagger documentation
  const config = new DocumentBuilder()
    .setTitle('Mobile Config API')
    .setDescription('Configuration service for mobile applications')
    .setVersion('1.0')
    .build();
  const document = SwaggerModule.createDocument(app, config);
  SwaggerModule.setup('docs', app, document);

  const port = process.env.PORT || 3000;
  await app.listen(port);
  
  StructuredLogger.info('application_started', { port });
}

bootstrap();
```

## Часть 9: Docker и развертывание

### 9.1 Dockerfile

```dockerfile
FROM node:22-alpine

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production

# Copy application code
COPY . .

# Build the application
RUN npm run build

# Create non-root user
RUN addgroup -g 1001 -S nodejs
RUN adduser -S nestjs -u 1001

# Create app directory and set permissions
RUN mkdir -p /app/data /app/logs
RUN chown -R nestjs:nodejs /app

USER nestjs

EXPOSE 3000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:3000/health || exit 1

CMD ["node", "dist/main"]
```

### 9.2 docker-compose.yml

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    environment:
      NODE_ENV: development
      PORT: 3000
      LOG_LEVEL: info
      # URLs loaded dynamically - NOT hardcoded!
      BACKEND_JSONRPC_URL: https://api.application.com/jsonrpc/v2
      NOTIFICATIONS_JSONRPC_URL: https://notifications.application.com/jsonrpc/v1
      ASSETS_CDN_URLS: https://dhm.cdn.application.com,https://ehz.cdn.application.com
      DEFINITIONS_CDN_URLS: https://fmp.cdn.application.com,https://eau.cdn.application.com
    ports:
      - "3000:3000"
    volumes:
      - ./data:/app/data:ro
      - ./logs:/app/logs
    networks: [ web ]
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3000/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 5s

networks:
  web: {}
```

### 9.3 package.json

```json
{
  "name": "mobile-config-nestjs",
  "version": "1.0.0",
  "description": "Mobile app configuration service built with NestJS",
  "scripts": {
    "build": "nest build",
    "start": "nest start",
    "start:dev": "nest start --watch",
    "start:debug": "nest start --debug --watch",
    "start:prod": "node dist/main",
    "test": "jest",
    "test:watch": "jest --watch",
    "test:cov": "jest --coverage",
    "test:debug": "node --inspect-brk -r tsconfig-paths/register -r ts-node/register node_modules/.bin/jest --runInBand",
    "test:e2e": "jest --config ./test/jest-e2e.json"
  },
  "dependencies": {
    "@nestjs/common": "^10.0.0",
    "@nestjs/core": "^10.0.0",
    "@nestjs/platform-express": "^10.0.0",
    "@nestjs/config": "^3.0.0",
    "@nestjs/cache-manager": "^2.0.0",
    "@nestjs/swagger": "^7.0.0",
    "class-validator": "^0.14.0",
    "class-transformer": "^0.5.1",
    "cache-manager": "^5.0.0",
    "joi": "^17.9.0",
    "uuid": "^9.0.0",
    "swagger-ui-express": "^5.0.0"
  },
  "devDependencies": {
    "@nestjs/cli": "^10.0.0",
    "@nestjs/schematics": "^10.0.0",
    "@nestjs/testing": "^10.0.0",
    "@types/express": "^4.17.17",
    "@types/jest": "^29.5.2",
    "@types/node": "^20.3.1",
    "@types/supertest": "^2.0.12",
    "@types/uuid": "^9.0.0",
    "jest": "^29.5.0",
    "supertest": "^6.3.3",
    "ts-jest": "^29.1.0",
    "ts-loader": "^9.4.3",
    "ts-node": "^10.9.1",
    "tsconfig-paths": "^4.2.0",
    "typescript": "^5.1.3"
  }
}
```

## Часть 10: Тестирование

### 10.1 Юнит-тесты для SemVer утилиты (src/utils/__tests__/semver.util.spec.ts)

```typescript
import { SemVerUtil } from '../semver.util';

describe('SemVerUtil', () => {
  describe('parse', () => {
    it('should parse valid semver', () => {
      expect(SemVerUtil.parse('1.2.3')).toEqual({ major: 1, minor: 2, patch: 3 });
    });

    it('should throw error for invalid semver', () => {
      expect(() => SemVerUtil.parse('1.2')).toThrow('Invalid SemVer: 1.2');
    });
  });

  describe('isAssetsCompatible', () => {
    it('should return true for same major version', () => {
      expect(SemVerUtil.isAssetsCompatible('14.1.100', '14.3.688')).toBe(true);
    });

    it('should return false for different major version', () => {
      expect(SemVerUtil.isAssetsCompatible('14.1.100', '15.3.688')).toBe(false);
    });
  });

  describe('isDefinitionsCompatible', () => {
    it('should return true for same major and minor version', () => {
      expect(SemVerUtil.isDefinitionsCompatible('14.1.100', '14.1.487')).toBe(true);
    });

    it('should return false for different minor version', () => {
      expect(SemVerUtil.isDefinitionsCompatible('14.1.100', '14.2.487')).toBe(false);
    });
  });
});
```

### 10.2 E2E тесты (test/app.e2e-spec.ts)

```typescript
import { Test, TestingModule } from '@nestjs/testing';
import { INestApplication, ValidationPipe } from '@nestjs/common';
import * as request from 'supertest';
import { AppModule } from '../src/app.module';

describe('Mobile Config API (e2e)', () => {
  let app: INestApplication;

  beforeEach(async () => {
    const moduleFixture: TestingModule = await Test.createTestingModule({
      imports: [AppModule],
    }).compile();

    app = moduleFixture.createNestApplication();
    app.useGlobalPipes(new ValidationPipe({
      transform: true,
      whitelist: true,
      forbidNonWhitelisted: true,
    }));
    
    await app.init();
  });

  afterEach(async () => {
    await app.close();
  });

  describe('/health (GET)', () => {
    it('should return ok status', () => {
      return request(app.getHttpServer())
        .get('/health')
        .expect(200)
        .expect({ status: 'ok' });
    });
  });

  describe('/config (GET)', () => {
    it('should return config for valid request', () => {
      return request(app.getHttpServer())
        .get('/config?appVersion=14.1.100&platform=android')
        .expect(200)
        .expect((res) => {
          expect(res.body).toHaveProperty('backend_entry_point');
          expect(res.body).toHaveProperty('assets');
          expect(res.body).toHaveProperty('definitions');
          expect(res.body).toHaveProperty('notifications');
          
          // Verify URL structure
          expect(res.body.assets.urls).toBeInstanceOf(Array);
          expect(res.body.definitions.urls).toBeInstanceOf(Array);
          expect(res.body.assets.hash).toBeDefined();
          expect(res.body.definitions.hash).toBeDefined();
        });
    });

    it('should return 400 for invalid platform', () => {
      return request(app.getHttpServer())
        .get('/config?appVersion=14.1.100&platform=desktop')
        .expect(400)
        .expect((res) => {
          expect(res.body.error.message).toContain('Invalid platform: desktop');
        });
    });

    it('should return 400 for invalid version format', () => {
      return request(app.getHttpServer())
        .get('/config?appVersion=14.1&platform=android')
        .expect(400)
        .expect((res) => {
          expect(res.body.error.message).toContain('Invalid version format');
        });
    });

    it('should return 404 for non-existent configuration', () => {
      return request(app.getHttpServer())
        .get('/config?appVersion=99.99.99&platform=android')
        .expect(404)
        .expect((res) => {
          expect(res.body.error.message).toContain('Configuration not found');
        });
    });
  });
});
```

## Заключение

Это полное руководство по миграции проекта Mobile Config API с PHP/Phalcon на Node.js/NestJS. Основные преимущества NestJS версии:

1. __TypeScript__ - строгая типизация и лучшая IDE поддержка
2. __Декораторы__ - элегантная валидация и документация API
3. __Dependency Injection__ - чистая архитектура и тестируемость
4. __Встроенное кеширование__ - простая интеграция с Redis/Memory
5. __Swagger__ - автоматическая документация API
6. __Структурированное логирование__ - JSON логи из коробки

Команды для запуска:

```bash
npm run start:dev    # Development
npm run build && npm run start:prod  # Production
npm test            # Unit tests
npm run test:e2e    # E2E tests
docker compose up -d --build  # Docker
```

Проект полностью эквивалентен PHP версии по функциональности, но использует современные возможности Node.js экосистемы.
