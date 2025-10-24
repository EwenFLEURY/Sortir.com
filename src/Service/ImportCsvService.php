<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ImportCsvService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SiteRepository $siteRepository
    ) {}

    /**
     * Retourne le nombre de lignes importées.
     *
     * @throws UnavailableStream
     * @throws Exception
     */
    public function import(string $csvPath): int
    {
        if (!is_file($csvPath)) {
            throw new RuntimeException('Fichier introuvable.');
        }

        $reader = Reader::from($csvPath);
        $reader->setHeaderOffset(0);

        // Détection simple du séparateur
        $firstLine = $this->firstLine($csvPath);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ','))
            ? ';'
            : ',';
        $reader->setDelimiter($delimiter);

        $records = $reader->getRecords(); // iterable
        $count = 0;

        foreach ($records as $row) {
            $user = new User();
            $user->setRoles(['ROLE_USER']);
            $user->setEmail($row['email']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $row['password']));
            $user->setUsername($row['username']);
            $user->setNom($row['name']);
            $user->setPrenom($row['firstname']);
            $user->setTelephone($row['phone']);
            $user->setActif(true);
            $user->setSite($this->siteRepository->find($row['site']));

            $this->em->persist($user);

            $count++;
        }

        $this->em->flush();

        return $count;
    }

    private function firstLine(string $path): string
    {
        $f = fopen($path, 'rb');
        if (!$f) {
            return '';
        }
        $line = fgets($f) ?: '';
        fclose($f);
        return $line;
    }
}