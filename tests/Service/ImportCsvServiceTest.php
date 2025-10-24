<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\SiteRepository;
use App\Service\ImportCsvService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ImportCsvServiceTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir =
            sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            'import_' .
            bin2hex(random_bytes(6));
        mkdir($this->tmpDir, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeRecursively($this->tmpDir);
    }

    public function testImportWithCommaDelimiter(): void
    {
        $csvPath = $this->createCsvFile(
            [
                'email',
                'password',
                'username',
                'name',
                'firstname',
                'phone',
                'site',
            ],
            [
                [
                    'john@example.com',
                    'secret123',
                    'johnny',
                    'Doe',
                    'John',
                    '0102030405',
                    '42',
                ],
                [
                    'jane@example.com',
                    'secretABC',
                    'jane',
                    'Smith',
                    'Jane',
                    '0607080910',
                    '7',
                ],
            ],
            ','
        );

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
                self::assertInstanceOf(User::class, $entity);
            });
        $em->expects($this->once())->method('flush');

        $hashedPasswords = [];
        $passwordHasher = $this->createMock(
            UserPasswordHasherInterface::class
        );
        $passwordHasher
            ->expects($this->exactly(2))
            ->method('hashPassword')
            ->willReturnCallback(function ($user, string $plain) use (
                &$hashedPasswords
            ): string {
                $hashedPasswords[] = $plain;

                return 'hashed:' . $plain;
            });

        $siteIds = [];
        $siteRepository = $this->createMock(SiteRepository::class);
        $siteRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(function ($id) use (&$siteIds) {
                $siteIds[] = (string) $id;

                // Retourne null pour éviter une dépendance au type App\Entity\Site
                // (si vous voulez tester l'affectation du Site, retournez un objet Site)
                return null;
            });

        $service = new ImportCsvService(
            $em,
            $passwordHasher,
            $siteRepository
        );

        $count = $service->import($csvPath);

        self::assertSame(2, $count);
        self::assertCount(2, $persisted);

        // Vérifications basées sur des getters usuels (adaptez si besoin)
        $this->assertUserRow(
            $persisted[0],
            'john@example.com',
            'johnny',
            'hashed:secret123'
        );
        $this->assertUserRow(
            $persisted[1],
            'jane@example.com',
            'jane',
            'hashed:secretABC'
        );

        self::assertSame(['42', '7'], $siteIds);
        self::assertSame(['secret123', 'secretABC'], $hashedPasswords);
    }

    public function testImportWithSemicolonDelimiter(): void
    {
        $csvPath = $this->createCsvFile(
            [
                'email',
                'password',
                'username',
                'name',
                'firstname',
                'phone',
                'site',
            ],
            [
                [
                    'alpha@example.com',
                    'pwd1',
                    'alpha',
                    'A',
                    'AA',
                    '0101010101',
                    '100',
                ],
                [
                    'beta@example.com',
                    'pwd2',
                    'beta',
                    'B',
                    'BB',
                    '0202020202',
                    '200',
                ],
            ],
            ';'
        );

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
                self::assertInstanceOf(User::class, $entity);
            });
        $em->expects($this->once())->method('flush');

        $passwordHasher = $this->createMock(
            UserPasswordHasherInterface::class
        );
        $passwordHasher
            ->expects($this->exactly(2))
            ->method('hashPassword')
            ->willReturnCallback(fn($user, string $plain): string => 'hashed:' .
                $plain);

        $siteRepository = $this->createMock(SiteRepository::class);
        $capturedIds = [];
        $siteRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(function ($id) use (&$capturedIds) {
                $capturedIds[] = (string) $id;

                return null;
            });

        $service = new ImportCsvService(
            $em,
            $passwordHasher,
            $siteRepository
        );

        $count = $service->import($csvPath);

        self::assertSame(2, $count);
        self::assertCount(2, $persisted);
        $this->assertUserRow(
            $persisted[0],
            'alpha@example.com',
            'alpha',
            'hashed:pwd1'
        );
        $this->assertUserRow(
            $persisted[1],
            'beta@example.com',
            'beta',
            'hashed:pwd2'
        );
        self::assertSame(['100', '200'], $capturedIds);
    }

    public function testImportThrowsWhenFileIsMissing(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(
            UserPasswordHasherInterface::class
        );
        $siteRepository = $this->createMock(SiteRepository::class);

        $service = new ImportCsvService(
            $em,
            $passwordHasher,
            $siteRepository
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fichier introuvable.');

        $service->import($this->tmpDir . DIRECTORY_SEPARATOR . 'missing.csv');
    }

    public function testImportWithOnlyHeaderReturnsZeroAndFlushes(): void
    {
        $csvPath = $this->createCsvFile(
            [
                'email',
                'password',
                'username',
                'name',
                'firstname',
                'phone',
                'site',
            ],
            [],
            ','
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->once())->method('flush');

        $passwordHasher = $this->createMock(
            UserPasswordHasherInterface::class
        );
        $passwordHasher->expects($this->never())->method('hashPassword');

        $siteRepository = $this->createMock(SiteRepository::class);
        $siteRepository->expects($this->never())->method('find');

        $service = new ImportCsvService(
            $em,
            $passwordHasher,
            $siteRepository
        );

        $count = $service->import($csvPath);

        self::assertSame(0, $count);
    }

    private function createCsvFile(
        array $headers,
        array $rows,
        string $delimiter
    ): string {
        $path =
            $this->tmpDir .
            DIRECTORY_SEPARATOR .
            'data_' .
            bin2hex(random_bytes(4)) .
            '.csv';

        $lines = [];
        $lines[] = implode($delimiter, $headers);

        foreach ($rows as $row) {
            // Pas de quoting nécessaire ici car les valeurs ne contiennent
            // ni le délimiteur ni des retours à la ligne.
            $lines[] = implode($delimiter, $row);
        }

        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);

        return $path;
    }

    private function assertUserRow(
        object $user,
        string $expectedEmail,
        string $expectedUsername,
        string $expectedHashedPassword
    ): void {
        if (method_exists($user, 'getEmail')) {
            self::assertSame($expectedEmail, $user->getEmail());
        }
        if (method_exists($user, 'getUsername')) {
            self::assertSame($expectedUsername, $user->getUsername());
        }
        if (method_exists($user, 'getPassword')) {
            self::assertSame($expectedHashedPassword, $user->getPassword());
        }
        if (method_exists($user, 'isActif')) {
            self::assertTrue($user->isActif());
        }
        if (method_exists($user, 'getRoles')) {
            self::assertContains('ROLE_USER', $user->getRoles());
        }
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