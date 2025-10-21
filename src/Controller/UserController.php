<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use App\Service\ImportCsvService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/users', name: 'users_')]
final class UserController extends AbstractController
{
    private string $importCsvDirectory;

    public function __construct(
        private readonly UserRepository $userRepository,
        ParameterBagInterface $params,
    ) {
        $this->importCsvDirectory = $params->get('import_csv_users_directory');
    }

    #[IsGranted(UserVoter::CREATE)]
    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response
    {
        $userToCreate = new User();

        $form = $this->createForm(UserType::class, $userToCreate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                $userToCreate->setPassword($passwordHasher->hashPassword($userToCreate, $plainPassword));
            }

            $em->persist($userToCreate);
            $em->flush();

            $this->addFlash('success', 'L\'utilisateur à bien été créer');

            return $this->redirect($request->headers->get('referer'));
        }

        return $this->render('user/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[IsGranted(UserVoter::CREATE)]
    #[Route('/import', name: 'import', methods: ['POST'])]
    public function import(
        Request $request,
        ImportCsvService $importCsvService,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('import_csv_users',  $request->request->get('_token'))) {
            return $this->json([
                'success' => false,
                'message' => 'Token CSRF invalide.'
            ], 419);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if(!$file) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun fichier reçu.'
            ], 400);
        }

        $allowed = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
        ];
        if (!in_array($file->getMimeType() ?? '', $allowed, true)) {
            return $this->json([
                'success' => false,
                'message' => 'Type de fichier non autorisé (CSV attendu)',
            ], 415);
        }

        $targetDir = $this->importCsvDirectory;
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de créer le répertoire de dépôt',
            ], 500);
        }

        $safeName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = strtolower((string) $file->guessExtension() ?: 'csv');
        $stored = sprintf('%s_%s.%s', $safeName, bin2hex(random_bytes(6)), $ext);

        try {
            $moved = $file->move($targetDir, $stored);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }

        $fullPath = $moved->getRealPath() ?: $targetDir . '/' . $stored;

        try {
            $count = $importCsvService->import($fullPath);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur import: ' . $e->getMessage(),
            ], 500);
        }

        try {
            @unlink($fullPath);
        } catch (\Throwable $e) {}

        return $this->json([
            'success' => true,
            'message' => "Import terminé avec succès ($count lignes). Actualisation en cours ...",
        ], 200);
    }

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
