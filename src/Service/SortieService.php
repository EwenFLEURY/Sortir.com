<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Entity\Enum\Etat;
use App\Repository\SortieRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class SortieService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SortieRepository $sortiesRepository,
    ) {}

    public function updateEtat(Sortie $sortie, DateTimeImmutable $now): bool
    {
        // Si la sortie est cloturée, annulée ou vient juste d'être créer alors on ne fait rien
        if (in_array($sortie->getEtat(), [Etat::Cloturee, Etat::Annulee, Etat::Creee])) {
            return false;
        }

        $duree = $sortie->getDuree();
        $dateDebut = $sortie->getDateHeureDebut();
        $dateFin = $dateDebut->modify("+ $duree minutes");

        // Sinon on met à jour les données
        if ($dateFin <= $now) {
            $sortie->setEtat(Etat::Passee);
        } elseif ($dateDebut < $now) {
            $sortie->setEtat(Etat::Ouverte);
        } else {
            $sortie->setEtat(Etat::Activite);
        }

        return true;
    }

    public function updateEtatAll(): int
    {
        $now = new DateTimeImmutable();
        $sortiesFaites = 0;
        $sorties = $this->sortiesRepository->findAll();

        foreach ($sorties as $sortie) {
            if ($this->updateEtat($sortie, $now)) {
                $sortiesFaites ++;
            }
        }

        $this->em->flush();

        return $sortiesFaites;
    }
}