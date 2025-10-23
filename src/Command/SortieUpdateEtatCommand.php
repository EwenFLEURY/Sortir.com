<?php

namespace App\Command;

use App\Service\SortieService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand(
    name: 'app:sortie:update-etat',
    description: 'Met à jour le champs \'etat\' des entités Sortie.',
)]
#[AsPeriodicTask('10 minutes', schedule: 'default')]
class SortieUpdateEtatCommand extends Command
{
    public function __construct(
        private readonly SortieService $sortieService,
    )
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->sortieService->updateEtatAll();

        $io->success(sprintf('%d Sortie(s) mises à jour.', $count));

        return Command::SUCCESS;
    }
}
