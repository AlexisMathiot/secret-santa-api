<?php

namespace App\Command;

use App\Repository\InvitationRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:invitation:cleanup', 'Deletes old invitations from the database')]
class InvitationCleanupCommand extends Command
{
    public function __construct(
        private InvitationRepository $invitationRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run');
    }

    /**
     * @throws \DateMalformedStringException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('dry-run')) {
            $io->note('Dry mode enabled');

            $count = $this->invitationRepository->countOldRejected();
        } else {
            $count = $this->invitationRepository->deleteOldRejected();
        }

        $io->success(sprintf('Deleted "%d" old invitations.', $count));

        return Command::SUCCESS;
    }
}