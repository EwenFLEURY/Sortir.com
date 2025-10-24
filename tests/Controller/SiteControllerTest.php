<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\SiteController;
use App\Entity\Site;
use App\Form\SiteType;
use App\Repository\SiteRepository;
use App\Service\UrlService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

/**
 * Tests unitaires d'actions du SiteController.
 * - Pas de base de données
 * - Pas de templates Twig (render() est surchargé)
 * - Pas de vrai SiteType (createForm() est surchargé)
 * On vérifie la logique, les redirections et les flashs.
 */
final class SiteControllerTest extends TestCase
{
    public function testListRendersWithSites(): void
    {
        $repo = $this->createMock(SiteRepository::class);
        $urlService = $this->createMock(UrlService::class);

        $sites = [new Site(), new Site()];
        $repo->expects($this->once())
            ->method('findAll')
            ->willReturn($sites);

        $controller = new TestableSiteController($repo, $urlService);

        $response = $controller->list();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertCount(1, $controller->renderCalls);
        $call = $controller->renderCalls[0];
        $this->assertSame('site/sites.html.twig', $call['view']);
        $this->assertArrayHasKey('sites', $call['parameters']);
        $this->assertSame($sites, $call['parameters']['sites']);
    }

    public function testCreateGetRendersForm(): void
    {
        $repo = $this->createMock(SiteRepository::class);
        $urlService = $this->createMock(UrlService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new TestableSiteController($repo, $urlService);

        $request = Request::create('/site/create', 'GET');
        $session = new Session(new MockArraySessionStorage());

        $urlService->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);

        $response = $controller->create($request, $em, $session);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertCount(1, $controller->renderCalls);
        $call = $controller->renderCalls[0];
        $this->assertSame('site/create.html.twig', $call['view']);
        $this->assertArrayHasKey('siteForm', $call['parameters']);
        $this->assertInstanceOf(FormInterface::class, $call['parameters']['siteForm']);
    }

    public function testCreatePostValidPersistsFlushesFlashesAndRedirectsToReturnTo(): void
    {
        $repo = $this->createMock(SiteRepository::class);
        $urlService = $this->createMock(UrlService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new TestableSiteController($repo, $urlService);

        // On poste un champ 'site[name]' non vide pour satisfaire NotBlank
        $request = Request::create('/site/create', 'POST', [
            'site' => ['name' => 'Mon site'],
        ]);
        $session = new Session(new MockArraySessionStorage());

        $urlService->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);

        $urlService->expects($this->once())
            ->method('getFormReturnTo')
            ->with($session)
            ->willReturn('/retour');

        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Site::class));
        $em->expects($this->once())->method('flush');

        $response = $controller->create($request, $em, $session);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/retour', $response->getTargetUrl());

        $this->assertSame(
            [['type' => 'success', 'message' => 'Site Ajouter.']],
            $controller->flashes
        );
    }

    public function testCreatePostInvalidRendersFormDoesNotPersist(): void
    {
        $repo = $this->createMock(SiteRepository::class);
        $urlService = $this->createMock(UrlService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new TestableSiteController($repo, $urlService);

        // On poste un champ vide pour déclencher la contrainte NotBlank
        $request = Request::create('/site/create', 'POST', [
            'site' => ['name' => ''],
        ]);
        $session = new Session(new MockArraySessionStorage());

        $urlService->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);

        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $response = $controller->create($request, $em, $session);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertCount(1, $controller->renderCalls);
        $call = $controller->renderCalls[0];
        $this->assertSame('site/create.html.twig', $call['view']);
    }

    public function testEditGetRendersForm(): void
    {
        $repo = $this->createMock(SiteRepository::class);
        $urlService = $this->createMock(UrlService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new TestableSiteController($repo, $urlService);

        $site = new Site();
        $request = Request::create('/site/1/edit', 'GET');
        $session = new Session(new MockArraySessionStorage());

        $urlService->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);

        $response = $controller->edit($site, $em, $request, $session);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertCount(1, $controller->renderCalls);
        $call = $controller->renderCalls[0];
        $this->assertSame('site/edit.html.twig', $call['view']);
        $this->assertArrayHasKey('siteForm', $call['parameters']);
        $this->assertArrayHasKey('site', $call['parameters']);
        $this->assertSame($site, $call['parameters']['site']);
    }

    public function testEditPostValidPersistsFlushesFlashesAndRedirectsToListWhenNoReturnTo(): void
    {
        $repo = $this->createMock(SiteRepository::class);
        $urlService = $this->createMock(UrlService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new TestableSiteController($repo, $urlService);

        $site = new Site();

        $request = Request::create('/site/1/edit', 'POST', [
            'site' => ['name' => 'Nouveau nom'],
        ]);
        $session = new Session(new MockArraySessionStorage());

        $urlService->expects($this->once())
            ->method('setFormReturnTo')
            ->with($request, $session);

        $urlService->expects($this->once())
            ->method('getFormReturnTo')
            ->with($session)
            ->willReturn(null);

        $em->expects($this->once())
            ->method('persist')
            ->with($site);
        $em->expects($this->once())->method('flush');

        $response = $controller->edit($site, $em, $request, $session);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/site/', $response->getTargetUrl());

        $this->assertSame(
            [['type' => 'success', 'message' => 'Site modifié.']],
            $controller->flashes
        );
    }

    public function testDeleteRemovesFlushesFlashesAndRedirectsToReferer(): void
    {
        $repo = $this->createMock(SiteRepository::class);
        $urlService = $this->createMock(UrlService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new TestableSiteController($repo, $urlService);

        $site = new Site();
        $request = Request::create('/site/1/delete', 'GET');

        $urlService->expects($this->once())
            ->method('getReferer')
            ->with($request)
            ->willReturn('/precedent');

        $em->expects($this->once())
            ->method('remove')
            ->with($site);
        $em->expects($this->once())->method('flush');

        $response = $controller->delete($site, $em, $request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/precedent', $response->getTargetUrl());

        $this->assertSame(
            [['type' => 'success', 'message' => 'Site supprimé']],
            $controller->flashes
        );
    }

    public function testDeleteRedirectsToListWhenNoReferer(): void
    {
        $repo = $this->createMock(SiteRepository::class);
        $urlService = $this->createMock(UrlService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new TestableSiteController($repo, $urlService);

        $site = new Site();
        $request = Request::create('/site/1/delete', 'GET');

        $urlService->expects($this->once())
            ->method('getReferer')
            ->with($request)
            ->willReturn(null);

        $em->expects($this->once())
            ->method('remove')
            ->with($site);
        $em->expects($this->once())->method('flush');

        $response = $controller->delete($site, $em, $request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/site/', $response->getTargetUrl());

        $this->assertSame(
            [['type' => 'success', 'message' => 'Site supprimé']],
            $controller->flashes
        );
    }
}

/**
 * Contrôleur de test qui surcharge:
 * - render() pour ne pas exiger Twig
 * - createForm() pour éviter SiteType et dépendances app
 * - addFlash() pour capturer les flashs
 * - generateUrl() pour ne pas exiger le router
 */
final class TestableSiteController extends SiteController
{
    /** @var list<array{view:string,parameters:array}> */
    public array $renderCalls = [];

    /** @var list<array{type:string,message:string}> */
    public array $flashes = [];

    private ?FormFactoryInterface $formFactory = null;

    protected function render(
        string $view,
        array $parameters = [],
        Response $response = null
    ): Response {
        $this->renderCalls[] = [
            'view' => $view,
            'parameters' => $parameters,
        ];

        return new Response('render:' . $view, Response::HTTP_OK);
    }

    protected function addFlash(string $type, mixed $message): void
    {
        $this->flashes[] = ['type' => $type, 'message' => (string) $message];
    }

    protected function createForm(
        string $type,
        mixed $data = null,
        array $options = []
    ): FormInterface {
        // On construit un formulaire minimal indépendant de SiteType.
        // - Nom "site" pour coller au nom utilisé dans la Request
        // - Un champ "name" non mappé, avec NotBlank, pour tester valid/invalid
        if ($this->formFactory === null) {
            $validator = Validation::createValidator();

            $this->formFactory = Forms::createFormFactoryBuilder()
                ->addExtension(new HttpFoundationExtension())
                ->addExtension(new ValidatorExtension($validator))
                ->getFormFactory();
        }

        $builder = $this->formFactory->createNamedBuilder(
            'site',
            FormType::class,
            $data
        );

        $builder->add('name', TextType::class, [
            'mapped' => false,
            'constraints' => [new NotBlank()],
        ]);

        return $builder->getForm();
    }

    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        // On simule uniquement la route de fallback utilisée par le contrôleur.
        if ($route === 'sites_list') {
            return '/site/';
        }

        return '/generated/' . $route;
    }
}