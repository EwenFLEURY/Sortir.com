<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Entity\Enum\Etat;
use App\Repository\SortieRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class SortieService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SortieRepository $sortiesRepository,
    ) {}

    public function updateEtat(Sortie $sortie, DateTimeImmutable $now): bool
    {
        $duree = $sortie->getDuree();
        $dateDebut = DateTime::createFromImmutable($sortie->getDateHeureDebut());
        $dateFin = $dateDebut->modify("+ $duree minutes");

        // Si la sortie est cloturée, annulée ou vient juste d'être créer alors on ne fait rien
        if (in_array($sortie->getEtat(), [Etat::Cloturee, Etat::Annulee, Etat::Creee])) {
            return false;
        }

        // Sinon on met à jour les données
        if ($dateFin <= $now) {
            $sortie->setEtat(Etat::Passee);
        } else if ($dateDebut < $now) {
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