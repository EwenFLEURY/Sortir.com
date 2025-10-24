<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\LieuController;
use App\Entity\Lieu;
use App\Entity\Ville;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use App\Service\UrlService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LieuControllerTest extends TestCase
{
    private function makeController(
        ?LieuRepository $lieuRepo = null,
        ?UrlService $urlService = null
    ): TestableLieuController {
        return new TestableLieuController(
            $lieuRepo ?? $this->createMock(LieuRepository::class),
            $urlService ?? $this->createMock(UrlService::class)
        );
    }

    public function testListRendersWithLieuxFromRepository(): void
    {
        $lieu1 = $this->createMock(Lieu::class);
        $lieu2 = $this->createMock(Lieu::class);

        $lieuRepo = $this->createMock(LieuRepository::class);
        $lieuRepo->method('findAll')->willReturn([$lieu1, $lieu2]);

        $controller = $this->makeController($lieuRepo);

        $response = $controller->list();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('lieu/list.html.twig', $controller->lastRenderTemplate);
        $this->assertArrayHasKey('lieux', $controller->lastRenderParams);
        $this->assertSame(
            [$lieu1, $lieu2],
            $controller->lastRenderParams['lieux']
        );
    }

    public function testCreateGetRendersFormAndVilles(): void
    {
        $villeRepo = $this->createMock(VilleRepository::class);
        $v1 = $this->createMock(Ville::class);
        $v2 = $this->createMock(Ville::class);
        $villeRepo->method('findAll')->willReturn([$v1, $v2]);

        $em = $this->createMock(EntityManagerInterface::class);
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/lieu/create');

        $urlService = $this->createMock(UrlService::class);
        $urlService
            ->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);

        $controller = $this->makeController(null, $urlService);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->method('isSubmitted')->willReturn(false);

        $controller->setNextForm($form);

        $response = $controller->create($villeRepo, $request, $session, $em);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'lieu/create.html.twig',
            $controller->lastRenderTemplate
        );
        $this->assertSame([$v1, $v2], $controller->lastRenderParams['villes']);
        $this->assertSame($form, $controller->lastRenderParams['lieuForm']);
    }

    public function testCreatePostValidPersistsAndRedirectsToReturnTo(): void
    {
        $villeRepo = $this->createMock(VilleRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/lieu/create', 'POST');

        $urlService = $this->createMock(UrlService::class);
        $urlService
            ->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);
        $urlService
            ->method('getFormReturnTo')
            ->with($session)
            ->willReturn('/retour');

        $controller = $this->makeController(null, $urlService);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $controller->setNextForm($form);

        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Lieu::class));
        $em->expects($this->once())->method('flush');

        $response = $controller->create($villeRepo, $request, $session, $em);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/retour', $response->getTargetUrl());
        $this->assertArrayHasKey('success', $controller->flashes);
        $this->assertContains('Lieu Ajouter.', $controller->flashes['success']);
    }

    public function testCreatePostValidRedirectsToGeneratedWhenNoReturnTo(): void
    {
        $villeRepo = $this->createMock(VilleRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/lieu/create', 'POST');

        $urlService = $this->createMock(UrlService::class);
        $urlService
            ->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);
        $urlService
            ->method('getFormReturnTo')
            ->with($session)
            ->willReturn(null);

        $controller = $this->makeController(null, $urlService);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $controller->setNextForm($form);

        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Lieu::class));
        $em->expects($this->once())->method('flush');

        $response = $controller->create($villeRepo, $request, $session, $em);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/generated/lieux_list', $response->getTargetUrl());
    }

    public function testCreatePostInvalidRendersForm(): void
    {
        $villeRepo = $this->createMock(VilleRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/lieu/create', 'POST');

        $urlService = $this->createMock(UrlService::class);
        $urlService
            ->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);

        $controller = $this->makeController(null, $urlService);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);

        $controller->setNextForm($form);

        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $response = $controller->create($villeRepo, $request, $session, $em);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'lieu/create.html.twig',
            $controller->lastRenderTemplate
        );
        $this->assertArrayHasKey('villes', $controller->lastRenderParams);
        $this->assertSame($form, $controller->lastRenderParams['lieuForm']);
    }

    public function testEditGetRendersFormAndVilles(): void
    {
        $villeRepo = $this->createMock(VilleRepository::class);
        $villes = [
            $this->createMock(Ville::class),
            $this->createMock(Ville::class),
        ];
        $villeRepo->method('findAll')->willReturn($villes);

        $em = $this->createMock(EntityManagerInterface::class);
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/lieu/123/edit');

        $urlService = $this->createMock(UrlService::class);
        $urlService
            ->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);

        $controller = $this->makeController(null, $urlService);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->method('isSubmitted')->willReturn(false);

        $controller->setNextForm($form);

        $lieu = $this->createMock(Lieu::class);

        $response = $controller->edit(
            $villeRepo,
            $lieu,
            $em,
            $request,
            $session
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('lieu/edit.html.twig', $controller->lastRenderTemplate);
        $this->assertSame($villes, $controller->lastRenderParams['villes']);
        $this->assertSame($form, $controller->lastRenderParams['lieuForm']);
        $this->assertSame($lieu, $controller->lastRenderParams['lieu']);
    }

    public function testEditPostValidPersistsAndRedirects(): void
    {
        $villeRepo = $this->createMock(VilleRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/lieu/42/edit', 'POST');

        $urlService = $this->createMock(UrlService::class);
        $urlService
            ->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);
        $urlService
            ->method('getFormReturnTo')
            ->with($session)
            ->willReturn('/retour-edit');

        $controller = $this->makeController(null, $urlService);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $controller->setNextForm($form);

        $lieu = $this->createMock(Lieu::class);

        $em->expects($this->once())->method('persist')->with($lieu);
        $em->expects($this->once())->method('flush');

        $response = $controller->edit(
            $villeRepo,
            $lieu,
            $em,
            $request,
            $session
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/retour-edit', $response->getTargetUrl());
        $this->assertContains('Lieu modifié.', $controller->flashes['success']);
    }

    public function testDeleteRemovesEntityAndRedirects(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $request = Request::create('/lieu/7/delete');
        $lieu = $this->createMock(Lieu::class);

        $urlService = $this->createMock(UrlService::class);
        $urlService->method('getReferer')->with($request)->willReturn('/prev');

        $controller = $this->makeController(null, $urlService);

        $em->expects($this->once())->method('remove')->with($lieu);
        $em->expects($this->once())->method('flush');

        $response = $controller->delete($lieu, $request, $em);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/prev', $response->getTargetUrl());
        $this->assertContains('Lieu supprimé', $controller->flashes['success']);
    }

    public function testLieuInfoReturnsExpectedJson(): void
    {
        $ville = $this->createMock(Ville::class);
        $ville->method('getNom')->willReturn('Nantes');
        $ville->method('getCodePostal')->willReturn(44000);

        $lieu = $this->createMock(Lieu::class);
        $lieu->method('getVille')->willReturn($ville);
        $lieu->method('getRue')->willReturn('1 rue des Tests');
        $lieu->method('getLatitude')->willReturn(47.2184);
        $lieu->method('getLongitude')->willReturn(-1.5536);

        $controller = $this->makeController();

        $response = $controller->lieuInfo($lieu);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Nantes', $data['villeNom']);
        $this->assertSame('1 rue des Tests', $data['lieuRue']);
        $this->assertSame(44000, $data['lieuCodep']);
        $this->assertSame(47.2184, $data['villeLatitude']);
        $this->assertSame(-1.5536, $data['villeLongitude']);
    }
}

final class TestableLieuController extends LieuController
{
    /** @var array<string, list<string>> */
    public array $flashes = [];

    /** @var array<string, mixed>|null */
    public ?array $lastRenderParams = null;

    public ?string $lastRenderTemplate = null;

    public ?string $lastGeneratedUrl = null;

    private ?FormInterface $nextForm = null;

    public function setNextForm(FormInterface $form): void
    {
        $this->nextForm = $form;
    }

    public function addFlash(string $type, mixed $message): void
    {
        $this->flashes[$type] ??= [];
        $this->flashes[$type][] = (string) $message;
    }

    public function render(
        string $view,
        array $parameters = [],
        ?Response $response = null
    ): Response {
        $this->lastRenderTemplate = $view;
        $this->lastRenderParams = $parameters;

        return $response instanceof Response
            ? $response
            : new Response('OK', Response::HTTP_OK);
    }

    public function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    public function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $this->lastGeneratedUrl = '/generated/' . $route;
        return $this->lastGeneratedUrl;
    }

    public function createForm(
        string $type,
               $data = null,
        array $options = []
    ): FormInterface {
        if (!$this->nextForm) {
            throw new LogicException(
                'Aucun formulaire configuré dans le contrôleur de test.'
            );
        }
        $form = $this->nextForm;
        $this->nextForm = null; // consommé
        return $form;
    }
}