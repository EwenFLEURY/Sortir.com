<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Enum\Etat;
use App\Entity\Sortie;
use App\Repository\SortieRepository;
use App\Service\SortieService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SortieServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private SortieRepository $repo;
    private SortieService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(SortieRepository::class);
        $this->service = new SortieService($this->em, $this->repo);
    }

    #[DataProvider('provideNonUpdatableEtats')]
    public function testUpdateEtatReturnsFalseForNonUpdatableStates(
        Etat $etat
    ): void {
        $sortie = $this->createMock(Sortie::class);

        $sortie->expects($this->once())
            ->method('getEtat')
            ->willReturn($etat);

        $sortie->expects($this->never())->method('getDuree');
        $sortie->expects($this->never())->method('getDateHeureDebut');
        $sortie->expects($this->never())->method('setEtat');

        $now = new DateTimeImmutable();

        $result = $this->service->updateEtat($sortie, $now);

        self::assertFalse($result);
    }

    public static function provideNonUpdatableEtats(): array
    {
        return [
            'Cloturee' => [Etat::Cloturee],
            'Annulee' => [Etat::Annulee],
            'Creee' => [Etat::Creee],
        ];
    }

    public function testUpdateEtatSetsPasseeWhenNowAfterEnd(): void
    {
        $now = new DateTimeImmutable();
        $start = $now->modify('-2 hours');

        $sortie = $this->createMock(Sortie::class);
        $sortie->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Ouverte);
        $sortie->expects($this->once())
            ->method('getDateHeureDebut')
            ->willReturn($start);
        $sortie->expects($this->once())->method('getDuree')->willReturn(30);
        $sortie->expects($this->once())
            ->method('setEtat')
            ->with(Etat::Passee);

        $result = $this->service->updateEtat($sortie, $now);

        self::assertTrue($result);
    }

    public function testUpdateEtatSetsPasseeWhenNowEqualsEnd(): void
    {
        $now = new DateTimeImmutable();
        $start = $now->modify('-30 minutes'); // end == now (30 min)

        $sortie = $this->createMock(Sortie::class);
        $sortie->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Ouverte);
        $sortie->expects($this->once())
            ->method('getDateHeureDebut')
            ->willReturn($start);
        $sortie->expects($this->once())->method('getDuree')->willReturn(30);
        $sortie->expects($this->once())
            ->method('setEtat')
            ->with(Etat::Passee);

        $result = $this->service->updateEtat($sortie, $now);

        self::assertTrue($result);
    }

    public function testUpdateEtatSetsOuverteWhenNowBetweenStartAndEnd(): void
    {
        $now = new DateTimeImmutable();
        $start = $now->modify('-10 minutes');

        $sortie = $this->createMock(Sortie::class);
        $sortie->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Activite);
        $sortie->expects($this->once())
            ->method('getDateHeureDebut')
            ->willReturn($start);
        $sortie->expects($this->once())->method('getDuree')->willReturn(60);
        $sortie->expects($this->once())
            ->method('setEtat')
            ->with(Etat::Ouverte);

        $result = $this->service->updateEtat($sortie, $now);

        self::assertTrue($result);
    }

    public function testUpdateEtatSetsActiviteWhenNowBeforeStart(): void
    {
        $now = new DateTimeImmutable();
        $start = $now->modify('+10 minutes');

        $sortie = $this->createMock(Sortie::class);
        $sortie->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Ouverte);
        $sortie->expects($this->once())
            ->method('getDateHeureDebut')
            ->willReturn($start);
        $sortie->expects($this->once())->method('getDuree')->willReturn(45);
        $sortie->expects($this->once())
            ->method('setEtat')
            ->with(Etat::Activite);

        $result = $this->service->updateEtat($sortie, $now);

        self::assertTrue($result);
    }

    public function testUpdateEtatAllUpdatesEligibleSortiesAndFlushes(): void
    {
        $baseNow = new DateTimeImmutable();

        // 3 non modifiables
        $s1 = $this->createMock(Sortie::class);
        $s1->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Annulee);
        $s1->expects($this->never())->method('setEtat');

        $s2 = $this->createMock(Sortie::class);
        $s2->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Creee);
        $s2->expects($this->never())->method('setEtat');

        $s3 = $this->createMock(Sortie::class);
        $s3->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Cloturee);
        $s3->expects($this->never())->method('setEtat');

        // Passée
        $s4 = $this->createMock(Sortie::class);
        $s4->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Ouverte);
        $s4->expects($this->once())
            ->method('getDateHeureDebut')
            ->willReturn($baseNow->modify('-2 hours'));
        $s4->expects($this->once())->method('getDuree')->willReturn(30);
        $s4->expects($this->once())
            ->method('setEtat')
            ->with(Etat::Passee);

        // Ouverte (entre début et fin)
        $s5 = $this->createMock(Sortie::class);
        $s5->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Activite);
        $s5->expects($this->once())
            ->method('getDateHeureDebut')
            ->willReturn($baseNow->modify('-10 minutes'));
        $s5->expects($this->once())->method('getDuree')->willReturn(60);
        $s5->expects($this->once())
            ->method('setEtat')
            ->with(Etat::Ouverte);

        // Activite (avant le début)
        $s6 = $this->createMock(Sortie::class);
        $s6->expects($this->once())
            ->method('getEtat')
            ->willReturn(Etat::Ouverte);
        $s6->expects($this->once())
            ->method('getDateHeureDebut')
            ->willReturn($baseNow->modify('+10 minutes'));
        $s6->expects($this->once())->method('getDuree')->willReturn(45);
        $s6->expects($this->once())
            ->method('setEtat')
            ->with(Etat::Activite);

        $this->repo->expects($this->once())
            ->method('findAll')
            ->willReturn([$s1, $s2, $s3, $s4, $s5, $s6]);

        $this->em->expects($this->once())->method('flush');

        $count = $this->service->updateEtatAll();

        self::assertSame(3, $count);
    }

    public function testUpdateEtatAllWithNoSortiesFlushesAndReturnsZero(): void
    {
        $this->repo->expects($this->once())->method('findAll')->willReturn([]);
        $this->em->expects($this->once())->method('flush');

        $count = $this->service->updateEtatAll();

        self::assertSame(0, $count);
    }
}