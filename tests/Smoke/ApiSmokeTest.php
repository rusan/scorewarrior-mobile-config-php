<?php                                                                                                                                                                
declare(strict_types=1);

namespace Tests\Smoke;

use Tests\TestCase;

final class ApiSmokeTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = getenv('BASE_URL') ?: 'http://127.0.0.1:8080';
    }

    private function httpGet(string $path): array
    {
        $url = $this->baseUrl . $path;
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 3]]);
        $body = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (is_array($http_response_header ?? null)) {
            foreach ($http_response_header as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) { $code = (int)$m[1]; break; }
            }
        }
        return [$code, $body === false ? '' : (string)$body];
    }

    public function testHealthOk(): void
    {
        [$code, $body] = $this->httpGet('/health');
        $this->assertSame(200, $code);
        $this->assertStringContainsString('"status":"ok"', preg_replace('/\s+/', '', $body));
    }

    public function testConfigValidAndroid(): void
    {
        [$code] = $this->httpGet('/config?appVersion=14.3.688&platform=android');
        $this->assertSame(200, $code);
    }

    public function testConfigInvalidPlatform400(): void
    {
        [$code] = $this->httpGet('/config?appVersion=14.1.553&platform=desktop');
        $this->assertSame(400, $code);
    }

    public function testConfigNotFound404(): void
    {
        [$code] = $this->httpGet('/config?appVersion=99.99.99&platform=android');
        $this->assertSame(404, $code);
    }

    public function testConfigMissingPlatform400(): void
    {
        [$code] = $this->httpGet('/config?appVersion=14.1.553');
        $this->assertSame(400, $code);
    }

    public function testConfigMissingAppVersion400(): void
    {
        [$code] = $this->httpGet('/config?platform=android');
        $this->assertSame(400, $code);
    }

    public function testConfigInvalidAppVersion400(): void
    {
        [$code] = $this->httpGet('/config?platform=android&appVersion=invalid');
        $this->assertSame(400, $code);
    }

    public function testConfigEmptyPlatform400(): void
    {
        [$code] = $this->httpGet('/config?platform=&appVersion=14.1.553');
        $this->assertSame(400, $code);
    }

    public function testConfigEmptyAppVersion400(): void
    {
        [$code] = $this->httpGet('/config?platform=android&appVersion=');
        $this->assertSame(400, $code);
    }

    public function testConfigValidResponseStructure(): void
    {
        [$code, $body] = $this->httpGet('/config?appVersion=14.1.553&platform=android');
        $this->assertSame(200, $code);
        
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('backend_entry_point', $data);
        $this->assertArrayHasKey('assetsVersion', $data);
        $this->assertArrayHasKey('definitionsVersion', $data);
        $this->assertArrayHasKey('notifications', $data);
        
        
        $this->assertArrayHasKey('jsonrpc_url', $data['backend_entry_point']);
        
        
        $this->assertArrayHasKey('version', $data['assetsVersion']);
        $this->assertArrayHasKey('hash', $data['assetsVersion']);
        $this->assertArrayHasKey('urls', $data['assetsVersion']);
        $this->assertIsArray($data['assetsVersion']['urls']);
        
        
        $this->assertArrayHasKey('version', $data['definitionsVersion']);
        $this->assertArrayHasKey('hash', $data['definitionsVersion']);
        $this->assertArrayHasKey('urls', $data['definitionsVersion']);
        $this->assertIsArray($data['definitionsVersion']['urls']);
        
        
        $this->assertArrayHasKey('jsonrpc_url', $data['notifications']);
    }

    public function testConfigDifferentVersions(): void
    {
        
        $versions = ['1.0.0', '2.5.10', '10.15.20', '14.1.553'];
        
        foreach ($versions as $version) {
            [$code] = $this->httpGet("/config?appVersion={$version}&platform=android");

            $this->assertContains($code, [200, 404], "Version {$version} returned unexpected code {$code}");
        }
    }

    public function testConfigCaching(): void
    {
        
        [$code1, $body1] = $this->httpGet('/config?appVersion=14.1.553&platform=android');
        [$code2, $body2] = $this->httpGet('/config?appVersion=14.1.553&platform=android');
        
        $this->assertSame($code1, $code2);
        $this->assertSame($body1, $body2);
    }

    public function testHealthResponseFormat(): void
    {
        [$code, $body] = $this->httpGet('/health');
        $this->assertSame(200, $code);
        
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame('ok', $data['status']);
    }
}