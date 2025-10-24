<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MainControllerTest extends WebTestCase
{
    public function testIndexRespondsSuccessfullyAndMatchesRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertResponseStatusCodeSame(200);
        self::assertRouteSame('main_index');
    }

    public function testIndexRendersMainTemplate(): void
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('GET', '/');

        $profile = $client->getProfile();
        self::assertNotNull($profile, 'Le profiler doit être activé.');

        $twigCollector = $profile->getCollector('twig');
        $templates = $twigCollector->getTemplates();

        self::assertIsArray($templates);
        self::assertArrayHasKey(
            'main/index.html.twig',
            $templates,
            'Le template main/index.html.twig doit être rendu.'
        );
    }

    public function testIndexDoesNotAllowNonGetMethods(): void
    {
        $client = static::createClient();
        $client->request('POST', '/');

        self::assertResponseStatusCodeSame(405);

        $allow = $client->getResponse()->headers->get('Allow');
        self::assertNotNull($allow);
        self::assertStringContainsString('GET', $allow);
    }
}