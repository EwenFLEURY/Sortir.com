<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use App\Security\Voter\LieuVoter;
use App\Security\Voter\SortieVoter;
use App\Service\UrlService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lieu', name: 'lieux_')]
class LieuController extends AbstractController
{

    public function __construct(
        private readonly LieuRepository $lieuRepository,
        private readonly UrlService $urlService,
    ) {}

    #[IsGranted(LieuVoter::VIEW)]
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        $lieux = $this->lieuRepository->findAll();

        return $this->render('lieu/list.html.twig', [
            'lieux' => $lieux,
        ]);
    }

    #[IsGranted(LieuVoter::CREATE)]
    #[Route('/create', name: 'create', methods: ['GET','POST'])]
    public function create(
        VilleRepository $villeRepository,
        Request $request,
        Session $session,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->urlService->setFormReturnTo($request, $session);

        $villes = $villeRepository->findAll();
        $lieu = new Lieu();
        $lieuForm = $this->createForm(LieuType::class, $lieu);
        $lieuForm->handleRequest($request);

        if ($lieuForm->isSubmitted() && $lieuForm->isValid()) {
            $entityManager->persist($lieu);
            $entityManager->flush();

            $this->addFlash('success', 'Lieu Ajouter.');

            return $this->redirect($this->urlService->getFormReturnTo($session) ?? $this->generateUrl('lieux_list'));
        }

        return $this->render('lieu/create.html.twig', ['lieuForm' => $lieuForm, 'villes' => $villes]);
    }

    #[IsGranted(LieuVoter::EDIT)]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(
        VilleRepository $villeRepository,
        Lieu $lieu,
        EntityManagerInterface $entityManager,
        Request $request,
        Session $session,
    ): Response
    {
        $this->urlService->setFormReturnTo($request, $session);

        $villes = $villeRepository->findAll();

        $lieuForm = $this->createForm(LieuType::class, $lieu);

        $lieuForm->handleRequest($request);

        if ($lieuForm->isSubmitted() && $lieuForm->isValid()) {
            $entityManager->persist($lieu);
            $entityManager->flush();

            $this->addFlash('success','Lieu modifié.');

            return $this->redirect($this->urlService->getFormReturnTo($session) ?? $this->generateUrl('lieux_list'));
        }

        return $this->render('lieu/edit.html.twig', ['lieuForm' => $lieuForm, 'villes' => $villes, 'lieu' => $lieu]);
    }

    #[IsGranted(LieuVoter::DELETE)]
    #[Route('/{id}/delete', name: 'delete', methods: ['GET'])]
    public function delete(
        Lieu $lieu,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $em->remove($lieu);
        $em->flush();

        $this->addFlash('success', 'Lieu supprimé');

        return $this->redirect($this->urlService->getReferer($request) ?? $this->generateUrl('lieux_list'));
    }

    #[IsGranted(SortieVoter::CREATE)]
    #[Route('/lieu-info/{id}', name: 'lieu_info', methods: ['GET'])]
    #[Cache(maxage: 600, smaxage: 600, public: true)]
    public function lieuInfo(Lieu $lieu): JsonResponse
    {
        return new JsonResponse([
            'villeNom' => $lieu->getVille()?->getNom(),
            'lieuRue' => $lieu->getRue(),
            'lieuCodep' => $lieu->getVille()->getCodePostal(),
            'villeLatitude' => $lieu->getLatitude(),
            'villeLongitude' => $lieu->getLongitude(),
        ]);
    }
}
