<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/users', name: 'users_')]
final class UserController extends AbstractController
{
    #[IsGranted(UserVoter::READ)]
    #[Route('/{id}', name: 'view', methods: ['GET', 'POST'])]
    public function view(
        User $userToModify,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        return $this->render('user/view.html.twig', [
            'controller_name' => 'UserController',
            'user' => $userToModify,
        ]);
    }

    #[IsGranted(UserVoter::EDIT, 'userToModify')]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        User $userToModify,
        Request $request,
        EntityManagerInterface $em,
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $userToModify);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour');
            return $this->redirectToRoute('users_view', ['id' => $userToModify->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'controller_name' => 'UserController',
            'form' => $form,
            'user' => $userToModify,
        ]);
    }
}
