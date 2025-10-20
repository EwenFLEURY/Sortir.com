<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sorties', name: 'sorties_')]
final class SortieController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('sortie/sorties.html.twig', [
        ]);
    }
}
