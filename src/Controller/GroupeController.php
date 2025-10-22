<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\Ville;
use App\Form\GroupeType;
use App\Repository\GroupeRepository;
use App\Repository\UserRepository;
use App\Security\Voter\GroupeVoter;
use App\Security\Voter\SortieVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/groupes', name: 'groupes_')]
final class GroupeController extends AbstractController
{
    public function __construct(
        private readonly GroupeRepository $groupeRepository,
        private readonly UserRepository $userRepository,
    ) {

    }
    #[IsGranted(GroupeVoter::VIEW)]
    #[Route('/list', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, EntityManagerInterface $manager): Response
    {
        $user = $this->userRepository->findByMail($this->getUser()->getUserIdentifier());
        $groupes = $user->getGroupes();
        return $this->render('groupe/groupes.html.twig', [
            'groupes' => $groupes,
        ]);
    }

    #[IsGranted(GroupeVoter::ADD)]
    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add( EntityManagerInterface $entityManager, Request $request): Response
    {
        $group = new Groupe();
        $groupeForm = $this->createForm(GroupeType::class, $group,
        [
            'user' => $this->getUser(),
        ]);
        $groupeForm->handleRequest($request);
        if ($groupeForm->isSubmitted() && $groupeForm->isValid()) {
            $entityManager->persist($group);
            $entityManager->flush();
            $this->addFlash('success',"Groupe créé");
            return $this->redirectToRoute('groupes_list');
        }
        return $this->render('groupe/add.html.twig', [
            'groupeForm' => $groupeForm,
        ]);
    }

    #[IsGranted(GroupeVoter::EDIT)]
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET','POST'])]
    public function edit(Groupe $groupe,
                         Request $request,
                         EntityManagerInterface $entityManager
    ): Response
    {
        $groupeForm = $this->createForm(GroupeType::class, $groupe);
        $groupeForm->handleRequest($request);
        if ($groupeForm->isSubmitted() && $groupeForm->isValid()) {
            $entityManager->persist($groupe);
            $entityManager->flush();
            $this->addFlash('success',"Ville modifier avec succes");
            return $this->redirectToRoute('groupes_list');
        }
        return $this->render('groupe/edit.html.twig', [
            'groupeForm' => $groupeForm,
        ]);
    }

    #[IsGranted(GroupeVoter::DELETE)]
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['GET','POST'])]
    public function delete(Groupe $groupe,
                         Request $request,
                         EntityManagerInterface $entityManager
    ): Response
    {
        $entityManager->remove($groupe);
        $entityManager->flush();
        $this->addFlash('success',"Groupe supprimer avec succes");
        return $this->redirectToRoute('groupes_list');
    }
}
