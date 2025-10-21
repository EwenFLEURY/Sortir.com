<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/users', name: 'users_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    #[IsGranted(UserVoter::READ_LIST)]
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->userRepository->findAll(),
        ]);
    }

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
        UserPasswordHasherInterface $passwordHasher,
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $userToModify);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                $userToModify->setPassword($passwordHasher->hashPassword($userToModify, $plainPassword));
            }

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

    #[IsGranted(UserVoter::ENABLE)]
    #[Route('/{id}/enable', name: 'enable', methods: ['POST'])]
    public function enable(
        User $userToModify,
        Request $request,
        EntityManagerInterface $em,
    ) : Response {
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $userToModify->getId(), $token)) {
            $this->addFlash('warning', 'Token CSRF invalide.');
            return $this->redirect($request->headers->get('referer'));
        }

        $userToModify->setActif(true);
        $em->flush();

        $this->addFlash('success', 'Utilisateur activé.');

        return $this->redirect($request->headers->get('referer'));
    }

    #[IsGranted(UserVoter::DISABLE)]
    #[Route('/{id}/disable', name: 'disable', methods: ['POST'])]
    public function diable(
        User $userToModify,
        Request $request,
        EntityManagerInterface $em,
    ) : Response {
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $userToModify->getId(), $token)) {
            $this->addFlash('warning', 'Token CSRF invalide.');
            return $this->redirect($request->headers->get('referer'));
        }

        $userToModify->setActif(false);
        $em->flush();

        $this->addFlash('success', 'Utilisateur désactivé.');

        return $this->redirect($request->headers->get('referer'));
    }

    #[IsGranted(UserVoter::DELETE)]
    #[Route('/{id}/delete', name: 'delete', methods: ['POST', 'DELETE'])]
    public function delete(
        User $userToModify,
        Request $request,
        EntityManagerInterface $em,
    ) : Response {
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $userToModify->getId(), $token)) {
            $this->addFlash('warning', 'Token CSRF invalide.');
            return $this->redirect($request->headers->get('referer'));
        }

        $em->remove($userToModify);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé.');

        return $this->redirect($request->headers->get('referer'));
    }
}
