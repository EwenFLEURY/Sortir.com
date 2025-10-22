<?php

namespace App\Controller;

use App\Entity\Enum\Etat;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\User;
use App\Form\SortieType;
use App\Repository\LieuRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Security\Voter\SortieVoter;
use DateTimeImmutable;
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
    ) {}

    #[IsGranted(SortieVoter::VIEW_LIST)]
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

    #[IsGranted(SortieVoter::CREATE)]
    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function sortieCreate(LieuRepository $lieuRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $lieux = $lieuRepository->findAll();
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            /** @var User $user */
            $user = $this->getUser();

            $sortie->setOrganisateur($user);
            $sortie->setSite($user->getSite());

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

    #[IsGranted(SortieVoter::SUBSCRIBE)]
    #[Route('/{id}/subscribe', name: 'subscribe', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function subscribe(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $participants = $sortie->getParticipants()->toArray();
        $userParticipant = false;

        /** @var User $user */
        $user = $this->getUser();

        foreach ($participants as $participant) {
            if ($participant === $user) {
                $userParticipant = true;
            }
        }

        if ($sortie->getEtat() == Etat::Ouverte && !$userParticipant && $sortie->getNbInscriptionMax() >= count($participants) + 1 && $sortie->getDateLimiteInscription()->getTimestamp() > (new DateTimeImmutable())->getTimestamp()) {
            $sortie->addParticipant($user);
            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success','Inscription réussite.');

            return $this->redirectToRoute('sorties_list');
        }

        $this->addFlash('danger','Erreur lors de l\'inscription');

        return $this->redirectToRoute('sorties_list');
    }

    #[IsGranted(SortieVoter::SUBSCRIBE)]
    #[Route('/{id}/unsubscribe', name: 'unsubscribe', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function unsubscribe(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $participants = $sortie->getParticipants()->toArray();
        $userParticipant = false;

        /** @var User $user */
        $user = $this->getUser();

        foreach ($participants as $participant) {
            if ($participant === $user) {
                $userParticipant = true;
            }
        }

        if ($sortie->getEtat() == Etat::Ouverte && $userParticipant) {
            $sortie->removeParticipant($user);
            $entityManager->persist($sortie);
            $entityManager->flush();
            $this->addFlash('success','Désinscription réussite.');
            return $this->redirectToRoute('sorties_list');
        }

        $this->addFlash('danger','Erreur lors de la d\'ésinscription');
        return $this->redirectToRoute('sorties_list');
    }

    #[IsGranted(SortieVoter::CANCEL, 'sortie')]
    #[Route('/{id}/cancel', name: 'cancel', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function cancel(
        Sortie $sortie,
        EntityManagerInterface $entityManager
    ): Response
    {
        $sortie->setEtat(Etat::Annulee);
        $entityManager->persist($sortie);
        $entityManager->flush();
        $this->addFlash('succes',"La sortie est annulée");
        return $this->redirectToRoute('sorties_list');
    }

    #[IsGranted(SortieVoter::VIEW)]
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Sortie $sortie): Response
    {
        return $this->render('sortie/show.html.twig', ['sortie' => $sortie,'participants' => $sortie->getParticipants()->toArray()]);
    }

    #[IsGranted(SortieVoter::EDIT,'sortie')]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(LieuRepository $lieuRepository, Sortie $sortie, EntityManagerInterface $entityManager, Request $request): Response
    {
        $lieux = $lieuRepository->findAll();
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $entityManager->persist($sortie);
            $entityManager->flush();
            $this->addFlash('success','Sortie modifié.');
            return $this->redirectToRoute('sorties_list');
        }
        return $this->render('sortie/edit.html.twig', ['sortieForm' => $sortieForm, 'lieux' => $lieux, 'sortie' => $sortie]);
    }

    #[IsGranted(SortieVoter::DELETE,'sortie')]
    #[Route('/{id}/delete', name: 'delete', methods: ['GET'])]
    public function delete(Sortie $sortie, EntityManagerInterface $em): Response
    {
        $em->remove($sortie);
        $em->flush();
        $this->addFlash('success', 'Sortie supprimé');
        return $this->redirectToRoute('sorties_list');
    }
}
