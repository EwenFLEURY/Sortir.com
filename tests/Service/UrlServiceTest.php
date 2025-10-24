<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\UrlService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class UrlServiceTest extends TestCase
{
    private UrlService $service;

    protected function setUp(): void
    {
        $this->service = new UrlService();
    }

    public function testGetRefererReturnsNullWhenNoHeader(): void
    {
        $request = Request::create('https://app.local/current');

        self::assertNull($this->service->getReferer($request));
    }

    public function testGetRefererReturnsNullWhenExternalAndSafeByDefault(): void
    {
        $request = Request::create('https://app.local/current');
        $request->headers->set('referer', 'https://evil.com/previous');

        self::assertNull($this->service->getReferer($request));
    }

    public function testGetRefererReturnsValueWhenExternalAndSafeFalse(): void
    {
        $request = Request::create('https://app.local/current');
        $request->headers->set('referer', 'https://evil.com/previous');

        self::assertSame(
            'https://evil.com/previous',
            $this->service->getReferer($request, false)
        );
    }

    public function testGetRefererReturnsValueWhenRelativeInternal(): void
    {
        $request = Request::create('https://app.local/current');
        $request->headers->set('referer', '/list?page=1');

        self::assertSame('/list?page=1', $this->service->getReferer($request));
    }

    public function testGetRefererReturnsValueWhenAbsoluteSameOrigin(): void
    {
        $request = Request::create('https://app.local/current');
        $request->headers->set('referer', 'https://app.local/previous?x=1');

        self::assertSame(
            'https://app.local/previous?x=1',
            $this->service->getReferer($request)
        );
    }

    public function testSetFormReturnToStoresOnGetAndGetFormReturnToRemoves(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('https://app.local/form');
        $request->headers->set('referer', '/items/42');

        $this->service->setFormReturnTo($request, $session);

        $value = $this->service->getFormReturnTo($session);
        self::assertSame('/items/42', $value);

        // Après remove, la valeur n'existe plus
        self::assertNull($this->service->getFormReturnTo($session));
    }

    public function testSetFormReturnToDoesNothingOnPost(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('https://app.local/form', 'POST');
        $request->headers->set('referer', '/items/42');

        $this->service->setFormReturnTo($request, $session);

        // Pas stocké car méthode POST
        self::assertNull($this->service->getFormReturnTo($session));
    }

    public function testSetFormReturnToIgnoresExternalRefererOnGet(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('https://app.local/form');
        $request->headers->set('referer', 'https://evil.com/out');

        $this->service->setFormReturnTo($request, $session);

        self::assertNull($this->service->getFormReturnTo($session));
    }

    #[DataProvider('provideIsSafeInternalUrlCases')]
    public function testIsSafeInternalUrl(
        ?string $url,
        string $baseUrl,
        bool $expected
    ): void {
        $request = Request::create($baseUrl);

        self::assertSame(
            $expected,
            $this->service->isSafeInternalUrl($url, $request)
        );
    }

    public static function provideIsSafeInternalUrlCases(): array
    {
        return [
            'null' => [null, 'https://app.local/current', false],
            'empty' => ['', 'https://app.local/current', false],
            'relative path' => ['/a/b?x=1', 'https://app.local/current', true],
            'absolute same origin https' => [
                'https://app.local/a',
                'https://app.local/current',
                true,
            ],
            'absolute same origin http (different scheme)' => [
                'http://app.local/a',
                'https://app.local/current',
                false,
            ],
            'absolute same origin but different port' => [
                'https://app.local:444/a',
                'https://app.local/current',
                false,
            ],
            'absolute same origin without explicit port (matches request)' => [
                // Request is https -> port 443; no port provided in URL
                'https://app.local/a',
                'https://app.local/current',
                true,
            ],
            'different host' => [
                'https://other.local/a',
                'https://app.local/current',
                false,
            ],
            'invalid url' => ['://not-a-valid-url', 'https://app.local/current', false],
        ];
    }
}