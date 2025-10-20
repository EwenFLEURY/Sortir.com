<?php

namespace App\Controller;

use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sorties', name: 'sorties_')]
final class SortieController extends AbstractController
{
    public function __construct(
        private readonly SortieRepository $sortieRepository,
    ) {
    }
    #[Route('/', name: 'list', methods: ['GET','POST'])]
    public function list(): Response
    {
        $sorties = $this->sortieRepository->findAll();
        return $this->render('sortie/sorties.html.twig', [
            'sorties' => $sorties,
        ]);
    }

}
