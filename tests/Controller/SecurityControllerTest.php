<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

final class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageRespondsSuccessfullyAndMatchesRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertResponseStatusCodeSame(200);
        self::assertRouteSame('app_login');
    }

    public function testLoginTemplateIsRendered(): void
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('GET', '/login');

        $profile = $client->getProfile();
        self::assertNotNull($profile, 'Le profiler doit être activé.');

        $twigCollector = $profile->getCollector('twig');
        $templates = $twigCollector->getTemplates();

        self::assertIsArray($templates);
        self::assertArrayHasKey(
            'security/login.html.twig',
            $templates,
            'Le template security/login.html.twig doit être rendu.'
        );
    }

    public function testLogoutRouteIsRegistered(): void
    {
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->getRouteCollection()->get('app_logout');

        self::assertInstanceOf(Route::class, $route);
        self::assertSame('/logout', $route->getPath());
    }
}