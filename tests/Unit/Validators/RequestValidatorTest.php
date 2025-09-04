<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Config\RequestParameterNames;
use App\Exceptions\ValidationException;
use App\Validators\RequestValidator;
use PHPUnit\Framework\TestCase;

class RequestValidatorTest extends TestCase
{
    private RequestValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RequestValidator();
    }

    public function testValidConfigRequest(): void
    {
        $params = [
            RequestParameterNames::PLATFORM => 'android',
            RequestParameterNames::APP_VERSION => '1.0.0',
            RequestParameterNames::ASSETS_VERSION => '1.0.0',
            RequestParameterNames::DEFINITIONS_VERSION => '1.0.0',
        ];

        
        $this->validator->validateConfigRequest($params);
        $this->assertTrue(true);
    }

    public function testValidConfigRequestWithNullOptionalVersions(): void
    {
        $params = [
            RequestParameterNames::PLATFORM => 'ios',
            RequestParameterNames::APP_VERSION => '2.5.10',
            RequestParameterNames::ASSETS_VERSION => null,
            RequestParameterNames::DEFINITIONS_VERSION => null,
        ];

        
        $this->validator->validateConfigRequest($params);
        $this->assertTrue(true);
    }

    public function testEmptyPlatform(): void
    {
        $params = [
            RequestParameterNames::PLATFORM => '',
            RequestParameterNames::APP_VERSION => '1.0.0',
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Platform parameter is required');
        $this->expectExceptionCode(400);

        $this->validator->validateConfigRequest($params);
    }

    public function testInvalidPlatform(): void
    {
        $params = [
            RequestParameterNames::PLATFORM => 'desktop',
            RequestParameterNames::APP_VERSION => '1.0.0',
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid platform: desktop');
        $this->expectExceptionCode(400);

        $this->validator->validateConfigRequest($params);
    }

    public function testEmptyAppVersion(): void
    {
        $params = [
            RequestParameterNames::PLATFORM => 'android',
            RequestParameterNames::APP_VERSION => '',
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('AppVersion parameter is required');
        $this->expectExceptionCode(400);

        $this->validator->validateConfigRequest($params);
    }

    public function testInvalidAppVersionFormat(): void
    {
        $params = [
            RequestParameterNames::PLATFORM => 'android',
            RequestParameterNames::APP_VERSION => '1.0',
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid version format: appVersion');
        $this->expectExceptionCode(400);

        $this->validator->validateConfigRequest($params);
    }

    public function testInvalidAssetsVersionFormat(): void
    {
        $params = [
            RequestParameterNames::PLATFORM => 'android',
            RequestParameterNames::APP_VERSION => '1.0.0',
            RequestParameterNames::ASSETS_VERSION => '1.0',
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid version format: assetsVersion');
        $this->expectExceptionCode(400);

        $this->validator->validateConfigRequest($params);
    }

    public function testInvalidDefinitionsVersionFormat(): void
    {
        $params = [
            RequestParameterNames::PLATFORM => 'android',
            RequestParameterNames::APP_VERSION => '1.0.0',
            RequestParameterNames::DEFINITIONS_VERSION => 'invalid',
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid version format: definitionsVersion');
        $this->expectExceptionCode(400);

        $this->validator->validateConfigRequest($params);
    }

    public function testValidPlatforms(): void
    {
        $validPlatforms = $this->validator->getValidPlatforms();
        
        $this->assertContains('android', $validPlatforms);
        $this->assertContains('ios', $validPlatforms);
        $this->assertCount(2, $validPlatforms);
    }

    public function testSemVerPattern(): void
    {
        $pattern = $this->validator->getSemVerPattern();
        
        $this->assertEquals('/^\d+\.\d+\.\d+$/', $pattern);
        
        
        $this->assertMatchesRegularExpression($pattern, '1.0.0');
        $this->assertMatchesRegularExpression($pattern, '10.25.100');
        $this->assertMatchesRegularExpression($pattern, '0.0.1');
        
        
        $this->assertDoesNotMatchRegularExpression($pattern, '1.0');
        $this->assertDoesNotMatchRegularExpression($pattern, '1.0.0.0');
        $this->assertDoesNotMatchRegularExpression($pattern, '1.0.0-beta');
        $this->assertDoesNotMatchRegularExpression($pattern, 'invalid');
    }

    public function testValidVersionFormats(): void
    {
        $validVersions = [
            '1.0.0',
            '0.0.1',
            '10.25.100',
            '999.999.999',
        ];

        foreach ($validVersions as $version) {
            $params = [
                RequestParameterNames::PLATFORM => 'android',
                RequestParameterNames::APP_VERSION => $version,
            ];

            
            $this->validator->validateConfigRequest($params);
        }
        
        
        $this->assertCount(4, $validVersions);
    }

    public function testInvalidVersionFormats(): void
    {
        $invalidVersions = [
            '1.0',
            '1.0.0.0',
            '1.0.0-beta',
            'v1.0.0',
            '1.0.0.1',
            'invalid',
            '',
        ];

        foreach ($invalidVersions as $version) {
            $params = [
                RequestParameterNames::PLATFORM => 'android',
                RequestParameterNames::APP_VERSION => $version,
            ];

            $this->expectException(ValidationException::class);
            $this->validator->validateConfigRequest($params);
        }
    }
}
