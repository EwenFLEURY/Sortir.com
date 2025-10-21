<?php

namespace App\Controller;

use App\Entity\Enum\Etat;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\LieuRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sorties', name: 'sorties_')]
final class SortieController extends AbstractController
{
    public function __construct(
        private readonly SortieRepository $sortieRepository,
        private readonly SiteRepository $siteRepository,

    ) {
    }
    #[Route('/', name: 'list', methods: ['GET','POST'])]
    public function list(): Response
    {
        $sorties = $this->sortieRepository->findAll();
        $sites = $this->siteRepository->findAll();
        return $this->render('sortie/sorties.html.twig', [
            'sorties' => $sorties,
            'sites' => $sites,
        ]);
    }
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function sortieCreate(LieuRepository $lieuRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $lieux = $lieuRepository->findAll();
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $sortie->setOrganisateur($this->getUser());
            $sortie->setSite($this->getUser()->getSite());
            if ($request->request->has('enregistrer')) {
                $sortie->setEtat(Etat::Creee);
            } elseif ($request->request->has('publier')) {
                $sortie->setEtat(Etat::Ouverte);
            }
            $entityManager->persist($sortie);
            $entityManager->flush();
            $this->addFlash('success', 'Sortie Ajouter.');
            return $this->redirectToRoute('sorties_list');
        }
        return $this->render('sortie/create.html.twig', ['sortieForm' => $sortieForm, 'lieux' => $lieux]);
    }

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

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/subscribe/{id}', name: 'subscribe', methods: ['GET'])]
    public function subscribe(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $participants = $sortie->getParticipants()->toArray();
        $userParticipant = false;
        foreach ($participants as $participant) {
            if ($participant === $this->getUser()) {
                $userParticipant = true;
            }
        }
        if ($sortie->getEtat() == Etat::Ouverte && !$userParticipant && $sortie->getNbInscriptionMax() >= count($participants) + 1) {
            $sortie->addParticipant($this->getUser());
            $entityManager->persist($sortie);
            $entityManager->flush();
            $this->addFlash('success','Inscription réussite.');
            return $this->redirectToRoute('sorties_list');
        }
        $this->addFlash('danger','Erreur lors de l\'inscription');
        return $this->redirectToRoute('sorties_list');
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/unsubscribe/{id}', name: 'unsubscribe', methods: ['GET'])]
    public function unsubscribe(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $participants = $sortie->getParticipants()->toArray();
        $userParticipant = false;
        foreach ($participants as $participant) {
            if ($participant === $this->getUser()) {
                $userParticipant = true;
            }
        }
        if ($sortie->getEtat() == Etat::Ouverte && $userParticipant) {
            $sortie->removeParticipant($this->getUser());
            $entityManager->persist($sortie);
            $entityManager->flush();
            $this->addFlash('success','Désinscription réussite.');
            return $this->redirectToRoute('sorties_list');
        }
        $this->addFlash('danger','Erreur lors de la d\'ésinscription');
        return $this->redirectToRoute('sorties_list');
    }
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/{id}/cancel', name: 'cancel', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function cancel(Sortie $sortie,
                           UserRepository $userRepository,
                           Request $request,
                           EntityManagerInterface $entityManager
    ): Response
    {
        $user = $userRepository->findByMail($this->getUser()->getUserIdentifier());

        if( $user->getNom() != $sortie->getOrganisateur()->getNom()) {
            $this->addFlash('error', "Tu n'est pas l'organisateur de la sortie");
            return $this->redirectToRoute('sorties_list');
        }

        $sortie->setEtat(Etat::Annulee);
        $entityManager->persist($sortie);
        $entityManager->flush();
        $this->addFlash('succes',"La sortie est annulée");
        return $this->redirectToRoute('sorties_list');
    }


}
