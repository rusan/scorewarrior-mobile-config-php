<?php
declare(strict_types=1);

namespace App\Validators;

use App\Config\RequestParameterNames;
use App\Config\HttpStatusCodes;
use App\Config\ValidationConstants;
use App\Exceptions\ValidationException;

class RequestValidator
{

    public function validateConfigRequest(array $params): void
    {
        $platform = $params[RequestParameterNames::PLATFORM] ?? '';
        $appVersion = $params[RequestParameterNames::APP_VERSION] ?? '';
        $assetsVersion = $params[RequestParameterNames::ASSETS_VERSION] ?? null;
        $definitionsVersion = $params[RequestParameterNames::DEFINITIONS_VERSION] ?? null;

        $this->validatePlatform($platform);
        $this->validateAppVersion($appVersion);
        $this->validateOptionalVersion($assetsVersion, RequestParameterNames::ASSETS_VERSION);
        $this->validateOptionalVersion($definitionsVersion, RequestParameterNames::DEFINITIONS_VERSION);
    }

    private function validatePlatform(string $platform): void
    {
        if (empty($platform)) {
            throw new ValidationException('Platform parameter is required', HttpStatusCodes::BAD_REQUEST);
        }

        if (!isset(ValidationConstants::VALID_PLATFORMS[$platform])) {
            throw new ValidationException("Invalid platform: {$platform}", HttpStatusCodes::BAD_REQUEST);
        }
    }

    private function validateAppVersion(string $appVersion): void
    {
        if (empty($appVersion)) {
            throw new ValidationException('AppVersion parameter is required', HttpStatusCodes::BAD_REQUEST);
        }

        if (!$this->isValidSemVer($appVersion)) {
            throw new ValidationException('Invalid version format: appVersion', HttpStatusCodes::BAD_REQUEST);
        }
    }

    private function validateOptionalVersion(?string $version, string $paramName): void
    {
        if ($version !== null && !$this->isValidSemVer($version)) {
            throw new ValidationException("Invalid version format: {$paramName}", HttpStatusCodes::BAD_REQUEST);
        }
    }

    private function isValidSemVer(?string $version): bool
    {
        return is_string($version) && preg_match(ValidationConstants::SEMVER_PATTERN, $version) === 1;
    }

    public function getValidPlatforms(): array
    {
        return array_keys(ValidationConstants::VALID_PLATFORMS);
    }

    public function getSemVerPattern(): string
    {
        return ValidationConstants::SEMVER_PATTERN;
    }
}
