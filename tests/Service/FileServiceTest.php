<?php

namespace App\Tests\Service;

use App\Service\FileService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FileServiceTest extends TestCase
{
    private readonly FileService $service;

    private readonly string $tmpDir;

    protected function setUp(): void
    {
        $this->service = new FileService();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fs_' . bin2hex(random_bytes(6));
        mkdir($this->tmpDir, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeRecursively($this->tmpDir);
    }

    public function testCreateDirectoryCreatesAndIsIdempotent(): void
    {
        $dir = $this->tmpDir . DIRECTORY_SEPARATOR . 'new_dir';
        self::assertFalse(is_dir($dir));

        $created = $this->service->createDirectory($dir);

        self::assertTrue($created);
        self::assertTrue(is_dir($dir));

        // Idempotent: renvoie true si le dossier existe déjà
        $again = $this->service->createDirectory($dir);
        self::assertTrue($again);
    }

    public function testCreateDirectoryReturnsFalseWhenParentIsAFile(): void
    {
        $parentFile = $this->createTempFile('as_file');
        $path = $parentFile . DIRECTORY_SEPARATOR . 'child';

        $created = $this->service->createDirectory($path);

        self::assertFalse($created);
        self::assertFalse(is_dir($path));
    }

    public function testIsFileMimeType(): void
    {
        $filePath = $this->createTempFile('mime.txt', 'some text');
        $uploaded = new UploadedFile(
            $filePath,
            'mime.txt',
            null,
            UPLOAD_ERR_OK,
            true // mode test pour bypass is_uploaded_file
        );

        self::assertTrue(
            $this->service->isFileMimeType($uploaded, ['text/plain'])
        );
        self::assertFalse(
            $this->service->isFileMimeType($uploaded, ['image/png'])
        );
    }

    public function testIsFileExists(): void
    {
        $existsPath = $this->createTempFile('exists.txt', 'x');
        $missingPath = $this->tmpDir . DIRECTORY_SEPARATOR . 'missing.txt';

        self::assertTrue($this->service->isFileExists($existsPath));
        self::assertFalse($this->service->isFileExists($missingPath));
    }

    public function testCreateSafeNameGeneratesRandomNameWithGuessedExt(): void
    {
        $path = $this->createTempFile('report.txt', 'lorem ipsum');
        $uploaded = new UploadedFile(
            $path,
            'report.TXT',
            null,
            UPLOAD_ERR_OK,
            true
        );

        $name = $this->service->createSafeName($uploaded);

        self::assertMatchesRegularExpression(
            '/^report_[0-9a-f]{12}\.txt$/',
            $name
        );
    }

    public function testCreateSafeNameFallsBackToDefaultExtension(): void
    {
        // Stub UploadedFile pour forcer guessExtension() à renvoyer null
        $stub = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalName'])
            ->getMock();

        $stub->method('guessExtension')->willReturn(null);
        $stub->method('getClientOriginalName')->willReturn('document');

        $name = $this->service->createSafeName($stub, 'dat');

        self::assertMatchesRegularExpression(
            '/^document_[0-9a-f]{12}\.dat$/',
            $name
        );
    }

    public function testMoveFileToDirectory(): void
    {
        $targetDir = $this->tmpDir . DIRECTORY_SEPARATOR . 'target';
        mkdir($targetDir, 0775, true);

        $sourcePath = $this->createTempFile('original.txt', 'abc');
        $uploaded = new UploadedFile(
            $sourcePath,
            'original.txt',
            null,
            UPLOAD_ERR_OK,
            true
        );

        $newName = 'renamed.txt';
        $movedPath = $this->service->moveFileToDirectory(
            $uploaded,
            $targetDir,
            $newName
        );

        self::assertTrue(is_file($movedPath));
        self::assertSame($newName, basename($movedPath));
        self::assertFalse(file_exists($sourcePath), 'Le fichier source doit être déplacé (et donc ne plus exister à l’emplacement d’origine).');
    }

    public function testSupprimerFichier(): void
    {
        $path = $this->createTempFile('to_delete.txt', 'x');

        self::assertTrue($this->service->supprimerFichier($path));
        self::assertFalse(file_exists($path));

        // Re-supprimer un fichier inexistant doit renvoyer false
        self::assertFalse($this->service->supprimerFichier($path));
    }

    private function createTempFile(
        string $name,
        string $content = 'hello'
    ): string {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . $name;

        file_put_contents($path, $content);

        return $path;
    }

    private function removeRecursively(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $child = $path . DIRECTORY_SEPARATOR . $item;
            $this->removeRecursively($child);
        }

        @rmdir($path);
    }

}