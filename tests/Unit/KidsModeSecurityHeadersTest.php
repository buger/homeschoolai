<?php

namespace Tests\Unit;

use App\Http\Middleware\KidsModeSecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KidsModeSecurityHeadersTest extends TestCase
{
    private $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new KidsModeSecurityHeaders;
    }

    #[Test]
    public function it_applies_security_headers_when_kids_mode_is_active()
    {
        Session::put('kids_mode_active', true);

        $request = Request::create('/test', 'GET');
        $request->server->set('HTTPS', 'on'); // Simulate HTTPS

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('test content');
        });

        // Check basic security headers
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));

        // Check HSTS header (only on HTTPS)
        $this->assertEquals('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }

    #[Test]
    public function it_does_not_apply_security_headers_when_kids_mode_is_inactive()
    {
        Session::forget('kids_mode_active');

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('test content');
        });

        // Headers should not be present
        $this->assertNull($response->headers->get('X-Frame-Options'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Permissions-Policy'));
    }

    #[Test]
    public function it_applies_restrictive_csp_policy()
    {
        Session::put('kids_mode_active', true);

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('test content');
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);

        // Check for restrictive policies
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("embed-src 'none'", $csp);
        $this->assertStringContainsString("child-src 'none'", $csp);
        $this->assertStringContainsString("frame-src 'none'", $csp);
        $this->assertStringContainsString("worker-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);

        // Allow necessary resources for HTMX and Tailwind
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
    }

    #[Test]
    public function it_applies_restrictive_permissions_policy()
    {
        Session::put('kids_mode_active', true);

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('test content');
        });

        $permissionsPolicy = $response->headers->get('Permissions-Policy');
        $this->assertNotNull($permissionsPolicy);

        // Check for disabled browser features
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
        $this->assertStringContainsString('accelerometer=()', $permissionsPolicy);
        $this->assertStringContainsString('gyroscope=()', $permissionsPolicy);
        $this->assertStringContainsString('magnetometer=()', $permissionsPolicy);
        $this->assertStringContainsString('usb=()', $permissionsPolicy);
        $this->assertStringContainsString('midi=()', $permissionsPolicy);
        $this->assertStringContainsString('encrypted-media=()', $permissionsPolicy);
        $this->assertStringContainsString('payment=()', $permissionsPolicy);
        $this->assertStringContainsString('web-share=()', $permissionsPolicy);
        $this->assertStringContainsString('display-capture=()', $permissionsPolicy);

        // Allow fullscreen for same origin
        $this->assertStringContainsString('fullscreen=(self)', $permissionsPolicy);
    }

    #[Test]
    public function it_disables_caching_for_sensitive_pages()
    {
        Session::put('kids_mode_active', true);

        // Create request for sensitive route
        $request = Request::create('/kids-mode/exit', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/kids-mode/exit', []);
            $route->name('kids-mode.exit');

            return $route;
        });

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('sensitive content');
        });

        // Check cache control headers (order may vary)
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('proxy-revalidate', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $this->assertEquals('0', $response->headers->get('Expires'));
    }

    #[Test]
    public function it_does_not_disable_caching_for_non_sensitive_pages()
    {
        Session::put('kids_mode_active', true);

        // Create request for non-sensitive route
        $request = Request::create('/dashboard/child/today', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/dashboard/child/today', []);
            $route->name('dashboard.child-today');

            return $route;
        });

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('regular content');
        });

        // Cache control headers from our middleware should not be present
        // (Laravel may add default cache control, so we check it doesn't have our specific values)
        $cacheControl = $response->headers->get('Cache-Control');
        if ($cacheControl) {
            $this->assertStringNotContainsString('proxy-revalidate', $cacheControl);
        }
        $this->assertNull($response->headers->get('Pragma'));
        $this->assertNull($response->headers->get('Expires'));
    }

    #[Test]
    public function it_does_not_set_hsts_header_on_http()
    {
        Session::put('kids_mode_active', true);

        $request = Request::create('/test', 'GET');
        // Don't set HTTPS flag, simulating HTTP request

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('test content');
        });

        // HSTS header should not be present on HTTP
        $this->assertNull($response->headers->get('Strict-Transport-Security'));

        // Other security headers should still be present
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    #[Test]
    public function it_identifies_sensitive_kids_mode_pages_correctly()
    {
        $middleware = new KidsModeSecurityHeaders;
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('isSensitiveKidsModePage');
        $method->setAccessible(true);

        $sensitiveRoutes = [
            'kids-mode.exit',
            'kids-mode.exit.validate',
            'kids-mode.settings',
            'kids-mode.pin.update',
            'kids-mode.pin.reset',
        ];

        foreach ($sensitiveRoutes as $routeName) {
            $request = Request::create('/test', 'GET');
            $request->setRouteResolver(function () use ($routeName) {
                $route = new \Illuminate\Routing\Route('GET', '/test', []);
                $route->name($routeName);

                return $route;
            });

            $this->assertTrue($method->invoke($middleware, $request), "Route {$routeName} should be sensitive");
        }

        // Test non-sensitive route
        $request = Request::create('/test', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/test', []);
            $route->name('dashboard.child-today');

            return $route;
        });

        $this->assertFalse($method->invoke($middleware, $request), 'Non-sensitive route should not trigger cache headers');
    }

    #[Test]
    public function it_builds_csp_with_proper_syntax()
    {
        Session::put('kids_mode_active', true);

        $request = Request::create('https://example.com/test', 'GET');

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('test content');
        });

        $csp = $response->headers->get('Content-Security-Policy');

        // Verify CSP is properly formatted (semicolon-separated directives)
        $directives = explode('; ', $csp);
        $this->assertGreaterThan(5, count($directives));

        // Each directive should not be empty
        foreach ($directives as $directive) {
            $this->assertNotEmpty(trim($directive));
        }
    }

    #[Test]
    public function it_builds_permissions_policy_with_proper_syntax()
    {
        Session::put('kids_mode_active', true);

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('test content');
        });

        $permissionsPolicy = $response->headers->get('Permissions-Policy');

        // Verify Permissions Policy is properly formatted (comma-separated policies)
        $policies = explode(', ', $permissionsPolicy);
        $this->assertGreaterThan(5, count($policies));

        // Each policy should have proper format (feature=allowlist)
        foreach ($policies as $policy) {
            $this->assertStringContainsString('=', trim($policy));
        }
    }
}
