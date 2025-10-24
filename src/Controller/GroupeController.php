<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Form\GroupeType;
use App\Repository\UserRepository;
use App\Security\Voter\GroupeVoter;
use App\Service\UrlService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/groupes', name: 'groupes_')]
class GroupeController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UrlService $urlService,
    ) {}

    #[IsGranted(GroupeVoter::VIEW)]
    #[Route('/list', name: 'list', methods: ['GET', 'POST'])]
    public function list(): Response
    {
        $user = $this->userRepository->findByMail($this->getUser()->getUserIdentifier());
        $groupes = $user->getGroupes();

        return $this->render('groupe/groupes.html.twig', [
            'groupes' => $groupes,
        ]);
    }

    #[IsGranted(GroupeVoter::ADD)]
    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add(
        EntityManagerInterface $entityManager,
        Request $request,
        Session $session,
    ): Response
    {
        $this->urlService->setFormReturnTo($request, $session);

        $group = new Groupe();

        $groupeForm = $this->createForm(GroupeType::class, $group, ['user' => $this->getUser()]);
        $groupeForm->handleRequest($request);

        if ($groupeForm->isSubmitted() && $groupeForm->isValid()) {
            $entityManager->persist($group);
            $entityManager->flush();

            $this->addFlash('success',"Groupe créé");

            return $this->redirect($this->urlService->getFormReturnTo($session) ?? $this->generateUrl('groupes_list'));
        }

        return $this->render('groupe/add.html.twig', [
            'groupeForm' => $groupeForm,
        ]);
    }

    #[IsGranted(GroupeVoter::EDIT, subject: 'groupe')]
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET','POST'])]
    public function edit(
        Groupe $groupe,
        Request $request,
        EntityManagerInterface $entityManager,
        Session $session,
    ): Response
    {
        $this->urlService->setFormReturnTo($request, $session);

        $groupeForm = $this->createForm(GroupeType::class, $groupe);
        $groupeForm->handleRequest($request);

        if ($groupeForm->isSubmitted() && $groupeForm->isValid()) {
            $entityManager->persist($groupe);
            $entityManager->flush();

            $this->addFlash('success',"Ville modifier avec succes");

            return $this->redirect($this->urlService->getFormReturnTo($session) ?? $this->generateUrl('groupes_list'));
        }

        return $this->render('groupe/edit.html.twig', [
            'groupeForm' => $groupeForm,
        ]);
    }

    #[IsGranted(GroupeVoter::DELETE, subject: 'groupe')]
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['GET','POST'])]
    public function delete(
        Groupe $groupe,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $entityManager->remove($groupe);
        $entityManager->flush();

        $this->addFlash('success',"Groupe supprimer avec succes");

        return $this->redirect($this->urlService->getReferer($request) ?? $this->generateUrl('groupes_list'));
    }
}
