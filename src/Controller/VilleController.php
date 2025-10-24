<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use App\Security\Voter\VilleVoter;
use App\Service\UrlService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/villes', name: 'villes_')]
class VilleController extends AbstractController
{
    public function __construct(
        private readonly VilleRepository $villeRepository,
        private readonly UrlService $urlService,
    ) { }

    #[IsGranted(VilleVoter::VIEW)]
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        $villes = $this->villeRepository->findAll();

        return $this->render('ville/villes.html.twig', [
            'villes' => $villes,
        ]);
    }

    #[IsGranted(VilleVoter::EDIT)]
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET','POST'])]
    public function edit(
        Ville $ville,
        Request $request,
        EntityManagerInterface $entityManager,
        Session $session,
    ): Response
    {
        $this->urlService->setFormReturnTo($request, $session);

        $villeForm = $this->createForm(VilleType::class, $ville);
        $villeForm->handleRequest($request);

        if ($villeForm->isSubmitted() && $villeForm->isValid()) {
            $entityManager->persist($ville);
            $entityManager->flush();

            $this->addFlash('success',"Ville modifier avec succes");

            return $this->redirect($this->urlService->getFormReturnTo($session) ?? $this->generateUrl('villes_list'));
        }

        return $this->render('ville/edit.html.twig', [
            'villeForm' => $villeForm,
        ]);
    }

    #[IsGranted(VilleVoter::ADD)]
    #[Route('/add', name: 'add', methods: ['GET','POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        Session $session,
    ): Response
    {
        $this->urlService->setFormReturnTo($request, $session);

        $ville = new Ville();

        $villeForm = $this->createForm(VilleType::class, $ville);
        $villeForm->handleRequest($request);

        if ($villeForm->isSubmitted() && $villeForm->isValid()) {
            $entityManager->persist($ville);
            $entityManager->flush();

            $this->addFlash('success',"Ville ajouter avec succes");

            return $this->redirect($this->urlService->getFormReturnTo($session) ?? $this->generateUrl('villes_list'));
        }

        return $this->render('ville/add.html.twig', [
            'villeForm' => $villeForm,
        ]);
    }

    #[IsGranted(VilleVoter::DELETE)]
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['GET','POST'])]
    public function delete(
        Ville $ville,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $entityManager->remove($ville);
        $entityManager->flush();

        $this->addFlash('success',"Ville supprimer avec succes");

        return $this->redirect($this->urlService->getReferer($request) ?? $this->generateUrl('villes_list'));
    }
}
