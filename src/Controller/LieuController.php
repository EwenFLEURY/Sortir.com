<?php

namespace App\Controller;

use App\Entity\Enum\Etat;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\User;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use App\Security\Voter\LieuVoter;
use App\Security\Voter\SortieVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lieu', name: 'lieux_')]
final class LieuController extends AbstractController
{

    public function __construct(
        private readonly LieuRepository $lieuRepository,
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
    public function create(VilleRepository $villeRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $villes = $villeRepository->findAll();
        $lieu = new Lieu();
        $lieuForm = $this->createForm(LieuType::class, $lieu);
        $lieuForm->handleRequest($request);

        if ($lieuForm->isSubmitted() && $lieuForm->isValid()) {

            $entityManager->persist($lieu);
            $entityManager->flush();

            $this->addFlash('success', 'Lieu Ajouter.');

            return $this->redirectToRoute('lieux_list');
        }

        return $this->render('lieu/create.html.twig', ['lieuForm' => $lieuForm, 'villes' => $villes]);
    }

    #[IsGranted(LieuVoter::EDIT)]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(VilleRepository $villeRepository, Lieu $lieu, EntityManagerInterface $entityManager, Request $request): Response
    {

        $villes = $villeRepository->findAll();
        $lieuForm = $this->createForm(LieuType::class, $lieu);
        $lieuForm->handleRequest($request);
        if ($lieuForm->isSubmitted() && $lieuForm->isValid()) {
            $entityManager->persist($lieu);
            $entityManager->flush();
            $this->addFlash('success','Lieu modifié.');
            return $this->redirectToRoute('lieux_list');
        }
        return $this->render('lieu/edit.html.twig', ['lieuForm' => $lieuForm, 'villes' => $villes, 'lieu' => $lieu]);
    }

    #[IsGranted(LieuVoter::DELETE)]
    #[Route('/{id}/delete', name: 'delete', methods: ['GET'])]
    public function delete(Lieu $lieu, EntityManagerInterface $em): Response
    {
        $em->remove($lieu);
        $em->flush();
        $this->addFlash('success', 'Lieu supprimé');
        return $this->redirectToRoute('lieux_list');
    }

    #[IsGranted(SortieVoter::CREATE)]
    #[Route('/lieu-info/{id}', name: 'lieu_info', methods: ['GET'])]
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
