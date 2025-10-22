<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\SiteType;
use App\Repository\SiteRepository;
use App\Security\Voter\SiteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[Route('/site', name: 'sites_')]
final class SiteController extends AbstractController
{

    public function __construct(
        private readonly SiteRepository $siteRepository,
    ) {}

    #[IsGranted(SiteVoter::VIEW)]
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        $sites = $this->siteRepository->findAll();

        return $this->render('site/sites.html.twig', [
            'sites' => $sites,
        ]);
    }
    #[IsGranted(SiteVoter::CREATE)]
    #[Route('/create', name: 'create', methods: ['GET','POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $site = new Site();
        $siteForm = $this->createForm(SiteType::class, $site);
        $siteForm->handleRequest($request);
        if ($siteForm->isSubmitted() && $siteForm->isValid()) {

            $entityManager->persist($site);
            $entityManager->flush();
            $this->addFlash('success', 'Site Ajouter.');
            return $this->redirectToRoute('sites_list');
        }
        return $this->render('site/create.html.twig', ['siteForm' => $siteForm]);
    }

    #[IsGranted(SiteVoter::EDIT)]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(Site $site, EntityManagerInterface $entityManager, Request $request): Response
    {
        $siteForm = $this->createForm(SiteType::class, $site);
        $siteForm->handleRequest($request);
        if ($siteForm->isSubmitted() && $siteForm->isValid()) {
            $entityManager->persist($site);
            $entityManager->flush();
            $this->addFlash('success','Site modifié.');
            return $this->redirectToRoute('sites_list');
        }
        return $this->render('site/edit.html.twig', ['siteForm' => $siteForm,'site' => $site]);
    }

    #[IsGranted(SiteVoter::DELETE)]
    #[Route('/{id}/delete', name: 'delete', methods: ['GET'])]
    public function delete(Site $site, EntityManagerInterface $em): Response
    {
        $em->remove($site);
        $em->flush();
        $this->addFlash('success', 'Site supprimé');
        return $this->redirectToRoute('sites_list');
    }
}