<?php

namespace App\Controller;

use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/villes', name: 'villes_')]
final class VilleController extends AbstractController
{
    public function __construct(
        private readonly VilleRepository $villeRepository,

    ) {
    }
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        $villes = $this->villeRepository->findAll();

        return $this->render('ville/villes.html.twig', [
            'villes' => $villes,
        ]);
    }
}
