<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\VilleController;
use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use App\Service\UrlService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class VilleControllerTest extends TestCase
{
    private function makeContainer(
        ?Environment $twig = null,
        ?FormFactoryInterface $formFactory = null,
        ?UrlGeneratorInterface $router = null,
        ?RequestStack $requestStack = null
    ): Container {
        $container = new Container();

        if ($twig) {
            $container->set('twig', $twig);
        }

        if ($formFactory) {
            $container->set('form.factory', $formFactory);
        }

        if ($router) {
            // AbstractController->generateUrl() utilise le service "router"
            $container->set('router', $router);
        }

        if ($requestStack) {
            $container->set('request_stack', $requestStack);
        }

        return $container;
    }

    private function makeValidFormFactory(): FormFactoryInterface
    {
        /** @var FormInterface&MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $form->method('handleRequest')
            ->willReturnCallback(
            /**
             * @return FormInterface
             */
                function () use ($form) {
                    return $form;
                }
            );

        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        // Pour les cas où une vue de formulaire serait rendue malgré tout
        $form->method('createView')->willReturn(new FormView());

        /** @var FormFactoryInterface&MockObject $formFactory */
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $formFactory->method('create')
            ->with(VilleType::class, $this->anything(), $this->anything())
            ->willReturn($form);

        return $formFactory;
    }

    public function testListRendersAllVilles(): void
    {
        $villes = [new Ville(), new Ville()];

        /** @var VilleRepository&MockObject $repo */
        $repo = $this->createMock(VilleRepository::class);
        $repo->expects($this->once())
            ->method('findAll')
            ->willReturn($villes);

        /** @var UrlService&MockObject $urlService */
        $urlService = $this->createMock(UrlService::class);

        /** @var Environment&MockObject $twig */
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with(
                'ville/villes.html.twig',
                $this->callback(function (array $params) use ($villes): bool {
                    return array_key_exists('villes', $params)
                        && $params['villes'] === $villes;
                })
            )
            ->willReturn('<html>LISTE DES VILLES</html>');

        $container = $this->makeContainer($twig);

        $controller = new VilleController($repo, $urlService);
        $controller->setContainer($container);

        $response = $controller->list();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('LISTE DES VILLES', $response->getContent());
    }

    public function testAddWithValidFormPersistsAndRedirectsToReturnTo(): void
    {
        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Ville::class));
        $em->expects($this->once())->method('flush');

        /** @var UrlService&MockObject $urlService */
        $urlService = $this->createMock(UrlService::class);
        $urlService->expects($this->once())
            ->method('setFormReturnTo')
            ->with($this->isInstanceOf(Request::class), $this->isInstanceOf(Session::class));

        // Simule "return to" existant pour éviter generateUrl()
        $urlService->method('getFormReturnTo')->willReturn('/retour');

        /** @var VilleRepository&MockObject $repo */
        $repo = $this->createMock(VilleRepository::class);

        $formFactory = $this->makeValidFormFactory();

        // Prépare session + RequestStack pour addFlash()
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'POST']);
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->makeContainer(null, $formFactory, null, $requestStack);

        $controller = new VilleController($repo, $urlService);
        $controller->setContainer($container);

        $response = $controller->add($request, $em, $session);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/retour', $response->getTargetUrl());

        $flashes = $session->getFlashBag()->get('success', []);
        $this->assertCount(1, $flashes);
        $this->assertSame('Ville ajouter avec succes', $flashes[0]);
    }

    public function testEditWithValidFormPersistsAndRedirectsToListIfNoReturnTo(): void
    {
        $ville = new Ville();

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($ville);
        $em->expects($this->once())->method('flush');

        /** @var UrlService&MockObject $urlService */
        $urlService = $this->createMock(UrlService::class);
        $urlService->expects($this->once())
            ->method('setFormReturnTo')
            ->with($this->isInstanceOf(Request::class), $this->isInstanceOf(Session::class));
        // Pas de returnTo => utilisation de generateUrl('villes_list')
        $urlService->method('getFormReturnTo')->willReturn(null);

        /** @var VilleRepository&MockObject $repo */
        $repo = $this->createMock(VilleRepository::class);

        $formFactory = $this->makeValidFormFactory();

        /** @var UrlGeneratorInterface&MockObject $router */
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')
            ->with('villes_list', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/villes/');

        // Session + RequestStack pour addFlash()
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'POST']);
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->makeContainer(null, $formFactory, $router, $requestStack);

        $controller = new VilleController($repo, $urlService);
        $controller->setContainer($container);

        $response = $controller->edit($ville, $request, $em, $session);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/villes/', $response->getTargetUrl());

        $flashes = $session->getFlashBag()->get('success', []);
        $this->assertCount(1, $flashes);
        $this->assertSame('Ville modifier avec succes', $flashes[0]);
    }

    public function testDeleteRemovesAndRedirectsToReferer(): void
    {
        $ville = new Ville();

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($ville);
        $em->expects($this->once())->method('flush');

        /** @var UrlService&MockObject $urlService */
        $urlService = $this->createMock(UrlService::class);
        $urlService->method('getReferer')->willReturn('/precedent');

        /** @var VilleRepository&MockObject $repo */
        $repo = $this->createMock(VilleRepository::class);

        // Session + Request pour cohérence avec addFlash()
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = new Request();
        $request->setSession($session);

        // RequestStack pour addFlash()
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->makeContainer(null, null, null, $requestStack);

        $controller = new VilleController($repo, $urlService);
        $controller->setContainer($container);

        $response = $controller->delete($ville, $request, $em);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/precedent', $response->getTargetUrl());

        $flashes = $session->getFlashBag()->get('success', []);
        $this->assertCount(1, $flashes);
        $this->assertSame('Ville supprimer avec succes', $flashes[0]);
    }
}