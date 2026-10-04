<?php

namespace Tests\Feature\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/ip', fn (Request $request) => $request->ip());
        Route::get('/_test/url', fn () => url('/'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function cloudflareAddresses(): array
    {
        return [
            'IPv4' => ['162.158.12.34'],
            'IPv6' => ['2606:4700::6810:1'],
        ];
    }

    #[DataProvider('cloudflareAddresses')]
    public function test_it_reads_the_visitors_ip_when_the_request_comes_through_cloudflare(string $cloudflare): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $cloudflare])
            ->withHeader('X-Forwarded-For', '203.0.113.9')
            ->get('/_test/ip')
            ->assertSee('203.0.113.9');
    }

    public function test_it_ignores_forwarded_ips_from_anyone_else_so_they_cant_be_faked(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->withHeader('X-Forwarded-For', '203.0.113.9')
            ->get('/_test/ip')
            ->assertSee('198.51.100.7')
            ->assertDontSee('203.0.113.9');
    }

    public function test_it_builds_https_urls_when_cloudflare_forwards_an_https_request(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '162.158.12.34'])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('http://gigwithme.app/_test/url')
            ->assertSee('https://gigwithme.app');
    }

    public function test_it_ignores_a_forwarded_scheme_from_anyone_else(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('http://gigwithme.app/_test/url')
            ->assertSee('http://gigwithme.app')
            ->assertDontSee('https://');
    }
}
