<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

class FileService
{
    /**
     * Créer un dossier si il n'existe pas
     * @param string $dirPath
     * @return bool Renvoi true si le dossier à bien été créer ou qu'il existait déjà sinon false
     */
    public function createDirectory(string $dirPath): bool
    {
        return !(!is_dir($dirPath) && !@mkdir($dirPath, 0775, true) && !is_dir($dirPath));
    }

    /**
     * Vérifie si un fichier à des MimeTypes autorisés
     * @param UploadedFile $file
     * @param string[] $allowedMimeTypes
     * @return bool
     */
    public function isFileMimeType(UploadedFile $file, array $allowedMimeTypes): bool
    {
        return in_array($file->getMimeType() ?? '', $allowedMimeTypes, true);
    }

    /**
     * Vérifie si le fichier existe
     * @param string $filePath
     * @return bool
     */
    public function isFileExists(string $filePath): bool
    {
        return file_exists($filePath);
    }

    /**
     * Créer un nom unique et aléatoire
     * @param UploadedFile $file
     * @param string $defaultExtension
     * @return string Le nouveau nom aléatoire
     */
    public function createSafeName(UploadedFile $file, string $defaultExtension = 'txt'): string
    {
        $safeName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = strtolower((string) $file->guessExtension() ?: $defaultExtension);

        try {
            $stored = sprintf('%s_%s.%s', $safeName, bin2hex(random_bytes(6)), $ext);
        } catch (Throwable) {}

        return $stored;
    }

    /**
     * Déplace un fichier dans un répertoire et renvoi le chemin
     * @param UploadedFile $file
     * @param string $dirPath
     * @param string $fileName
     * @return string Le chemin du fichier nouvellement déplacer
     */
    public function moveFileToDirectory(UploadedFile $file, string $dirPath, string $fileName): string
    {
        $moved = $file->move($dirPath, $fileName);

        return $moved->getRealPath() ?: $dirPath . '/' . $fileName;
    }

    /**
     * Supprime un fichier en fonction de son chemin
     * @param string $filePath
     * @return bool Retourne true si la suppression à fonctionner sinon false
     */
    public function supprimerFichier(string $filePath): bool
    {
        return @unlink($filePath);
    }
}
