<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\DependencyNames;
use App\Config\RequestParameterNames;
use App\Services\RequestParameterService;
use Phalcon\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class RequestParameterServiceTest extends TestCase
{
    private RequestParameterService $service;
    private Request&MockObject $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new RequestParameterService();
        $this->request = $this->createMock(Request::class);
    }

    public function testExtractConfigParametersWithAllParams(): void
    {
        $this->request->expects($this->exactly(4))
            ->method('getQuery')
            ->willReturnCallback(function($param, $filters, $default) {
                return match($param) {
                    RequestParameterNames::PLATFORM => 'android',
                    RequestParameterNames::APP_VERSION => '14.1.553',
                    DependencyNames::ASSETS => '14.10.976',
                    DependencyNames::DEFINITIONS => '14.1.996',
                    default => $default
                };
            });

        $result = $this->service->extractConfigParameters($this->request);

        $this->assertEquals([
            RequestParameterNames::PLATFORM => 'android',
            RequestParameterNames::APP_VERSION => '14.1.553',
            DependencyNames::ASSETS => '14.10.976',
            DependencyNames::DEFINITIONS => '14.1.996',
        ], $result);
    }

    public function testExtractConfigParametersWithRequiredOnly(): void
    {
        $this->request->expects($this->exactly(4))
            ->method('getQuery')
            ->willReturnCallback(function($param, $filters, $default) {
                return match($param) {
                    RequestParameterNames::PLATFORM => 'ios',
                    RequestParameterNames::APP_VERSION => '14.2.100',
                    DependencyNames::ASSETS => null,
                    DependencyNames::DEFINITIONS => null,
                    default => $default
                };
            });

        $result = $this->service->extractConfigParameters($this->request);

        $this->assertEquals([
            RequestParameterNames::PLATFORM => 'ios',
            RequestParameterNames::APP_VERSION => '14.2.100',
            DependencyNames::ASSETS => null,
            DependencyNames::DEFINITIONS => null,
        ], $result);
    }

    public function testExtractConfigParametersWithEmptyValues(): void
    {
        $this->request->expects($this->exactly(4))
            ->method('getQuery')
            ->willReturnCallback(function($param, $filters, $default) {
                return match($param) {
                    RequestParameterNames::PLATFORM => '',
                    RequestParameterNames::APP_VERSION => '',
                    DependencyNames::ASSETS => null,
                    DependencyNames::DEFINITIONS => null,
                    default => $default
                };
            });

        $result = $this->service->extractConfigParameters($this->request);

        $this->assertEquals([
            RequestParameterNames::PLATFORM => '',
            RequestParameterNames::APP_VERSION => '',
            DependencyNames::ASSETS => null,
            DependencyNames::DEFINITIONS => null,
        ], $result);
    }
}
