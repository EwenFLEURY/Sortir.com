<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\SortieController;
use App\Entity\Enum\Etat;
use App\Entity\Sortie;
use App\Entity\User;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Service\UrlService;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class SortieControllerTest extends KernelTestCase
{
    private RequestStack $requestStack;
    private TokenStorageInterface $tokenStorage;

    /** @var UrlService&MockObject */
    private UrlService $urlService;

    /** @var SortieRepository&MockObject */
    private SortieRepository $sortieRepository;

    /** @var SiteRepository&MockObject */
    private SiteRepository $siteRepository;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $em;

    private SessionInterface $session;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->requestStack = self::getContainer()->get(RequestStack::class);
        $this->tokenStorage = self::getContainer()->get(TokenStorageInterface::class);

        // Create and push a Request with a Session so addFlash works.
        $this->session = $this->createSession();
        $request = Request::create('/dummy');
        $request->setSession($this->session);
        $this->requestStack->push($request);

        // Mocks
        $this->urlService = $this->createMock(UrlService::class);
        $this->urlService
            ->method('getFormReturnTo')
            ->willReturn('/sorties/');
        $this->urlService
            ->method('getReferer')
            ->willReturn('/previous');

        $this->sortieRepository = $this->createMock(SortieRepository::class);
        $this->siteRepository = $this->createMock(SiteRepository::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testSubscribeAddsCurrentUserAndRedirectsWithFlash(): void
    {
        $user = $this->authenticateTestUser();

        $participants = new ArrayCollection([
            $this->makeUser('alice@example.test'),
            $this->makeUser('bob@example.test'),
        ]);

        /** @var Sortie&MockObject $sortie */
        $sortie = $this->createMock(Sortie::class);
        $sortie->method('getEtat')->willReturn(Etat::Ouverte);
        $sortie->method('getParticipants')->willReturn($participants);
        $sortie->method('getNbInscriptionMax')->willReturn(10);
        $sortie
            ->method('getDateLimiteInscription')
            ->willReturn((new DateTimeImmutable())->modify('+2 days'));
        $sortie
            ->expects(self::once())
            ->method('addParticipant')
            ->with($user);

        $this->em->expects(self::once())->method('persist')->with($sortie);
        $this->em->expects(self::once())->method('flush');

        $controller = $this->makeController();

        $response = $controller->subscribe(
            $sortie,
            $this->em,
            $this->currentRequest(),
            $this->session,
        );
        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isRedirect());
        // Vérifie bien la cible de la redirection renvoyée par UrlService
        self::assertSame('/sorties/', $response->headers->get('Location'));
        // Vérifie le contenu du message flash "success"
        self::assertSame(
            ['Inscription réussite.'],
            $this->session->getFlashBag()->peek('success')
        );
    }

    public function testSubscribeFailsWhenAlreadyParticipant(): void
    {
        $user = $this->authenticateTestUser('john@example.test');

        $participants = new ArrayCollection([$user]);

        /** @var Sortie&MockObject $sortie */
        $sortie = $this->createMock(Sortie::class);
        $sortie->method('getEtat')->willReturn(Etat::Ouverte);
        $sortie->method('getParticipants')->willReturn($participants);
        $sortie->method('getNbInscriptionMax')->willReturn(10);
        $sortie
            ->method('getDateLimiteInscription')
            ->willReturn((new DateTimeImmutable())->modify('+2 days'));

        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        $controller = $this->makeController();

        $response = $controller->subscribe(
            $sortie,
            $this->em,
            $this->currentRequest(),
            $this->session,
        );

        self::assertTrue($response->isRedirect());
        self::assertSame(
            ["Erreur lors de l'inscription"],
            $this->session->getFlashBag()->peek('danger')
        );
    }

    public function testUnsubscribeRemovesCurrentUserAndRedirectsWithFlash(): void
    {
        $user = $this->authenticateTestUser('sam@example.test');

        $participants = new ArrayCollection([
            $this->makeUser('alice@example.test'),
            $user,
        ]);

        /** @var Sortie&MockObject $sortie */
        $sortie = $this->createMock(Sortie::class);
        $sortie->method('getEtat')->willReturn(Etat::Ouverte);
        $sortie->method('getParticipants')->willReturn($participants);
        $sortie->expects(self::once())->method('removeParticipant')->with($user);

        $this->em->expects(self::once())->method('persist')->with($sortie);
        $this->em->expects(self::once())->method('flush');

        $controller = $this->makeController();

        $response = $controller->unsubscribe(
            $sortie,
            $this->em,
            $this->currentRequest(),
            $this->session,
        );

        self::assertTrue($response->isRedirect());
        self::assertSame(
            ['Désinscription réussite.'],
            $this->session->getFlashBag()->peek('success')
        );
    }

    public function testUnsubscribeFailsWhenNotParticipant(): void
    {
        $this->authenticateTestUser('leo@example.test');

        $participants = new ArrayCollection([
            $this->makeUser('alice@example.test'),
            $this->makeUser('bob@example.test'),
        ]);

        /** @var Sortie&MockObject $sortie */
        $sortie = $this->createMock(Sortie::class);
        $sortie->method('getEtat')->willReturn(Etat::Ouverte);
        $sortie->method('getParticipants')->willReturn($participants);

        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        $controller = $this->makeController();

        $response = $controller->unsubscribe(
            $sortie,
            $this->em,
            $this->currentRequest(),
            $this->session,
        );

        self::assertTrue($response->isRedirect());
        self::assertSame(
            ["Erreur lors de la d'ésinscription"],
            $this->session->getFlashBag()->peek('danger')
        );
    }

    public function testDeleteSetsEtatClotureeAndRedirects(): void
    {
        $this->authenticateTestUser('owner@example.test');

        /** @var Sortie&MockObject $sortie */
        $sortie = $this->createMock(Sortie::class);
        $sortie->expects(self::once())->method('setEtat')->with(Etat::Cloturee);

        $this->em->expects(self::once())->method('flush');

        // Ensure UrlService->getReferer returns a path so controller does not
        // need to call generateUrl().
        $controller = $this->makeController();

        // Put a referer on current Request
        $req = $this->currentRequest();
        $req->headers->set('referer', '/previous');

        $response = $controller->delete($sortie, $this->em, $req);

        self::assertTrue($response->isRedirect('/previous'));
        self::assertSame(
            ['Sortie supprimé'],
            $this->session->getFlashBag()->peek('success')
        );
    }

    //
    // Helpers
    //

    private function makeController(): SortieController
    {
        $controller = new SortieController(
            $this->sortieRepository,
            $this->siteRepository,
            $this->urlService
        );

        // Inject the real container so AbstractController features work
        // (addFlash, generateUrl if needed, getUser(), etc.).
        $controller->setContainer(self::getContainer());

        return $controller;
    }

    private function authenticateTestUser(
        string $email = 'user@example.test'
    ): User {
        $user = new User();
        // Adaptez ces setters aux champs de votre entité User
        $user->setEmail($email);
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);

        $token = new UsernamePasswordToken($user, 'test', $user->getRoles());
        $this->tokenStorage->setToken($token);

        return $user;
    }

    private function makeUser(string $email): User
    {
        $u = new User();
        $u->setEmail($email);
        $u->setPassword('x');
        $u->setRoles(['ROLE_USER']);

        return $u;
    }

    private function createSession(): SessionInterface
    {
        // Use a mock session storage so we don't depend on a real handler.
        $storage = new MockArraySessionStorage();
        $session = new Session($storage);
        $session->start();

        return $session;
    }

    private function currentRequest(): Request
    {
        $req = $this->requestStack->getCurrentRequest();

        if (!$req) {
            $req = Request::create('/dummy');
            $req->setSession($this->session);
            $this->requestStack->push($req);
        }

        return $req;
    }
}