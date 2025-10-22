<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use App\Service\FileService;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/users', name: 'users_')]
final class UserController extends AbstractController
{
    private readonly string $importCsvDirectory;
    private readonly string $usersProfilePicturesDirectory;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly FileService    $fileService,
        ParameterBagInterface           $params,
    ) {
        $this->importCsvDirectory = $params->get('import_csv_users_directory');
        $this->usersProfilePicturesDirectory = $params->get('users_profile_pictures_directory');
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
            /** @var UploadedFile $image */
            $image = $form->get('image')->getData();

            if ($plainPassword) {
                $userToCreate->setPassword($passwordHasher->hashPassword($userToCreate, $plainPassword));
            }

            $userToCreate->setRoles(['ROLE_USER']);

            if ($image) {
                $imageDirectory = $this->usersProfilePicturesDirectory;
                $allowedMimetypes = ['image/jpeg', 'image/png', 'image/gif'];
                if ($this->fileService->isFileMimeType($image, $allowedMimetypes)) {
                    $nomImage = $this->fileService->createSafeName($image, 'jpg');
                    if ($this->fileService->createDirectory($imageDirectory)) {
                        try {
                            $this->fileService->moveFileToDirectory($image, $imageDirectory, $nomImage);
                            $userToCreate->setImage($nomImage);
                        } catch (FileException) {
                           $this->addFlash('warning', 'Impossible de d\'éplacer l\'image de l\'utilisateur dans le répertoire de dépôt.');
                        }
                    } else {
                        $this->addFlash('warning', 'Impossible de créer le répertoire de dépôt pour l\'image de l\'utilisateur.');
                    }
                } else {
                    $this->addFlash('warning', 'Impossible d\'ajouter l\'image car n\'a pas un format valide (JPG ou PNG ou GIF).');
                }
            }

            $em->persist($userToCreate);
            $em->flush();

            $this->addFlash('success', 'L\'utilisateur à bien été créer');

            return $this->redirectToRoute('users_index');
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

        $allowed = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
        ];

        $targetDir = $this->importCsvDirectory;

        if(!$file) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun fichier reçu.'
            ], 400);
        }

        if (!$this->fileService->isFileMimeType($file, $allowed)) {
            return $this->json([
                'success' => false,
                'message' => 'Type de fichier non autorisé (CSV attendu)',
            ], 415);
        }

        if (!$this->fileService->createDirectory($targetDir)) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de créer le répertoire de dépôt',
            ], 500);
        }

        $stored = $this->fileService->createSafeName($file, 'csv');

        try {
            $fullPath = $this->fileService->moveFileToDirectory($file, $targetDir, $stored);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }

        try {
            $count = $importCsvService->import($fullPath);
        } catch (Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur import: ' . $e->getMessage(),
            ], 500);
        }

        $this->fileService->supprimerFichier($fullPath);

        return $this->json([
            'success' => true,
            'message' => "Import terminé avec succès ($count lignes). Actualisation en cours ...",
        ]);
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
    public function view(User $userToModify): Response {

        $imagePath = null;

        if ($userToModify->getImage() && $this->fileService->isFileExists($this->usersProfilePicturesDirectory . '/' . $userToModify->getImage())) {
            $imagePath = '/' . $this->usersProfilePicturesDirectory . '/' . $userToModify->getImage();
        }

        return $this->render('user/view.html.twig', [
            'user' => $userToModify,
            'imagePath' => $imagePath,
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
        $form = $this->createForm(UserType::class, $userToModify);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            /** @var UploadedFile $image */
            $image = $form->get('image')->getData();

            if ($plainPassword) {
                $userToModify->setPassword($passwordHasher->hashPassword($userToModify, $plainPassword));
            }

            if ($image) {
                $imageDirectory = $this->usersProfilePicturesDirectory;
                $allowedMimetypes = ['image/jpeg', 'image/png', 'image/gif'];
                if ($this->fileService->isFileMimeType($image, $allowedMimetypes)) {
                    $nomImage = $this->fileService->createSafeName($image, 'jpg');
                    if ($this->fileService->createDirectory($imageDirectory)) {
                        try {
                            $this->fileService->moveFileToDirectory($image, $imageDirectory, $nomImage);
                            // Si on avait déjà une image alors on la supprime
                            if ($userToModify->getImage() && !$this->fileService->supprimerFichier($imageDirectory . '/' . $userToModify->getImage())) {
                                $this->addFlash('warning', 'Impossible de supprimer l\'ancienne image de l\'utilisateur.');
                            }
                            $userToModify->setImage($nomImage);
                        } catch (FileException) {
                            $this->addFlash('warning', 'Impossible de d\'éplacer l\'image de l\'utilisateur dans le répertoire de dépôt.');
                        }
                    } else {
                        $this->addFlash('warning', 'Impossible de créer le répertoire de dépôt pour l\'image de l\'utilisateur.');
                    }
                } else {
                    $this->addFlash('warning', 'Impossible d\'ajouter l\'image car n\'a pas un format valide (JPG ou PNG ou GIF).');
                }
            }

            $em->flush();

            $this->addFlash('success', 'Profil mis à jour');

            return $this->redirectToRoute('users_view', ['id' => $userToModify->getId()]);
        }

        $imagePath = null;

        if ($userToModify->getImage() && $this->fileService->isFileExists($this->usersProfilePicturesDirectory . '/' . $userToModify->getImage())) {
            $imagePath = '/' . $this->usersProfilePicturesDirectory . '/' . $userToModify->getImage();
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form,
            'user' => $userToModify,
            'imagePath' => $imagePath,
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
        User $user,
        Request $request,
        EntityManagerInterface $em,
    ) : Response {
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $user->getId(), $token)) {
            $this->addFlash('warning', 'Token CSRF invalide.');
            return $this->redirect($request->headers->get('referer'));
        }

        // Suppresion de son image s'il en a une
        if ($user->getImage() && !$this->fileService->supprimerFichier($this->usersProfilePicturesDirectory . '/' . $user->getImage()))
        {
            $this->addFlash('warning', 'Imposssible de supprimer l\'image de l\'utilisateur.');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé.');

        return $this->redirect($request->headers->get('referer'));
    }
}
