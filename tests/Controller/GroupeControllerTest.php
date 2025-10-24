<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\GroupeController;
use App\Entity\Groupe;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use App\Service\UrlService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Core\User\UserInterface;
use RuntimeException;

final class GroupeControllerTest extends TestCase
{
    public function testList_renders_with_user_groupes(): void
    {
        $securityUser = new FakeAuthenticatedUser('user@example.test');

        // Return a proper App\Entity\User mock (NOT an anonymous class)
        $domainUser = $this->createMock(User::class);
        $domainUser
            ->method('getGroupes')
            ->willReturn(new ArrayCollection([
                new Groupe(),
                new Groupe(),
            ]));

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo
            ->expects($this->once())
            ->method('findByMail')
            ->with('user@example.test')
            ->willReturn($domainUser);

        $urlService = $this->createMock(UrlService::class);

        [$controller] = $this->makeController(
            userRepository: $userRepo,
            urlService: $urlService,
            securityUser: $securityUser
        );

        $response = $controller->list();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testAdd_get_renders_form(): void
    {
        $securityUser = new FakeAuthenticatedUser('user@example.test');

        $userRepo = $this->createMock(UserRepository::class);

        $urlService = $this->createMock(UrlService::class);
        $urlService
            ->expects($this->once())
            ->method('setFormReturnTo')
            ->with(
                $this->isInstanceOf(Request::class),
                $this->isInstanceOf(Session::class)
            );

        // Form stub: not submitted => render branch
        $form = $this->createStub(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);

        [$controller, $session, $request] = $this->makeController(
            userRepository: $userRepo,
            urlService: $urlService,
            securityUser: $securityUser,
            form: $form
        );

        $em = $this->createMock(EntityManagerInterface::class);

        $response = $controller->add($em, $request, $session);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testAdd_post_valid_persists_flushes_and_redirects(): void
    {
        $securityUser = new FakeAuthenticatedUser('user@example.test');

        $userRepo = $this->createMock(UserRepository::class);

        $urlService = $this->createMock(UrlService::class);
        $urlService->expects($this->once())->method('setFormReturnTo');
        $urlService
            ->expects($this->once())
            ->method('getFormReturnTo')
            ->willReturn('/back');

        $form = $this->createStub(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        [$controller, $session, $request] = $this->makeController(
            userRepository: $userRepo,
            urlService: $urlService,
            securityUser: $securityUser,
            form: $form
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Groupe::class));
        $em->expects($this->once())->method('flush');

        $request->setMethod('POST');

        $response = $controller->add($em, $request, $session);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/back', $response->headers->get('Location'));

        $flashes = $session->getFlashBag()->get('success');
        $this->assertContains('Groupe créé', $flashes);
    }

    public function testEdit_get_renders_form(): void
    {
        $userRepo = $this->createMock(UserRepository::class);

        $urlService = $this->createMock(UrlService::class);
        $urlService
            ->expects($this->once())
            ->method('setFormReturnTo')
            ->with(
                $this->isInstanceOf(Request::class),
                $this->isInstanceOf(Session::class)
            );

        $form = $this->createStub(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);

        [$controller, $session, $request] = $this->makeController(
            userRepository: $userRepo,
            urlService: $urlService,
            securityUser: new FakeAuthenticatedUser('user@example.test'),
            form: $form
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $groupe = new Groupe();

        $response = $controller->edit($groupe, $request, $em, $session);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testEdit_post_valid_persists_and_redirects(): void
    {
        $userRepo = $this->createMock(UserRepository::class);

        $urlService = $this->createMock(UrlService::class);
        $urlService->expects($this->once())->method('setFormReturnTo');
        $urlService
            ->expects($this->once())
            ->method('getFormReturnTo')
            ->willReturn('/back2');

        $form = $this->createStub(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        [$controller, $session, $request] = $this->makeController(
            userRepository: $userRepo,
            urlService: $urlService,
            securityUser: new FakeAuthenticatedUser('user@example.test'),
            form: $form
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $groupe = new Groupe();

        $em->expects($this->once())->method('persist')->with($groupe);
        $em->expects($this->once())->method('flush');

        $request->setMethod('POST');

        $response = $controller->edit($groupe, $request, $em, $session);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/back2', $response->headers->get('Location'));

        $flashes = $session->getFlashBag()->get('success');
        $this->assertContains('Ville modifier avec succes', $flashes);
    }

    public function testDelete_removes_and_redirects(): void
    {
        $userRepo = $this->createMock(UserRepository::class);

        $urlService = $this->createMock(UrlService::class);
        $urlService->expects($this->once())->method('getReferer')->willReturn(
            '/referer'
        );

        [$controller, $session, $request] = $this->makeController(
            userRepository: $userRepo,
            urlService: $urlService,
            securityUser: new FakeAuthenticatedUser('user@example.test')
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $groupe = new Groupe();

        $em->expects($this->once())->method('remove')->with($groupe);
        $em->expects($this->once())->method('flush');

        $response = $controller->delete($groupe, $request, $em);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/referer', $response->headers->get('Location'));

        $flashes = $session->getFlashBag()->get('success');
        $this->assertContains('Groupe supprimer avec succes', $flashes);
    }

    /**
     * Build a testable controller:
     * - Test subclass overrides getUser() to avoid SecurityBundle dependency
     * - Mini container with twig, form.factory, router, session, request_stack
     *
     * @return array{0: TestableGroupeController, 1: Session, 2: Request}
     */
    private function makeController(
        UserRepository $userRepository,
        UrlService $urlService,
        ?UserInterface $securityUser = null,
        ?FormInterface $form = null
    ): array {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        // Minimal Twig
        $twig = new class {
            public function render(string $view, array $params = []): string
            {
                return 'OK';
            }
        };

        // Minimal Router
        $router = new class implements UrlGeneratorInterface {
            public function generate(
                string $name,
                array $parameters = [],
                int $referenceType = self::ABSOLUTE_PATH
            ): string {
                return '/groupes/list';
            }

            public function setContext(RequestContext $context): void
            {

            }

            public function getContext(): RequestContext
            {
                return new RequestContext();
            }
        };

        // Form factory stub
        $formFactory = $this->createStub(FormFactoryInterface::class);
        if ($form instanceof FormInterface) {
            $form->method('handleRequest')->willReturnSelf();
            $formFactory->method('create')->willReturn($form);
        } else {
            $defaultForm = $this->createStub(FormInterface::class);
            $defaultForm->method('handleRequest')->willReturnSelf();
            $defaultForm->method('isSubmitted')->willReturn(false);
            $defaultForm->method('isValid')->willReturn(false);
            $formFactory->method('create')->willReturn($defaultForm);
        }

        // Container for AbstractController
        $container = new SimpleArrayContainer([
            'twig' => $twig,
            'router' => $router,
            'session' => $session,
            'request_stack' => $requestStack,
            'form.factory' => $formFactory,
        ]);

        // Use the test subclass that overrides getUser()
        $controller = new TestableGroupeController(
            $userRepository,
            $urlService,
            $securityUser
        );
        $controller->setContainer($container);

        return [$controller, $session, $request];
    }
}

/**
 * Controller test subclass that bypasses AbstractController::getUser()
 * to avoid the SecurityBundle requirement in unit tests.
 */
final class TestableGroupeController extends GroupeController
{
    public function __construct(
        UserRepository $userRepository,
        UrlService $urlService,
        private readonly ?UserInterface $testUser
    ) {
        parent::__construct($userRepository, $urlService);
    }

    public function getUser(): ?UserInterface
    {
        return $this->testUser;
    }
}

/**
 * Minimal User for tests.
 */
final class FakeAuthenticatedUser implements UserInterface
{
    /**
     * @param array<int, string> $roles
     */
    public function __construct(
        private readonly string $identifier,
        private readonly array $roles = ['ROLE_USER']
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /** @return array<int, string> */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }
}

/**
 * Simple PSR-11 container for tests.
 */
final class SimpleArrayContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $services;

    /**
     * @param array<string, mixed> $services
     */
    public function __construct(array $services = [])
    {
        $this->services = $services;
    }

    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new class("Service not found: $id")
                extends RuntimeException
                implements NotFoundExceptionInterface {
            };
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
}