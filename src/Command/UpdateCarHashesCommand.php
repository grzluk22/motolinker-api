<?php

namespace App\Command;

use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:car:update-hashes',
    description: 'Updates the hash column for all cars based on their properties',
)]
class UpdateCarHashesCommand extends Command
{
    public function __construct(
        private CarRepository $carRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Updating Car Hashes');

        $cars = $this->carRepository->findAll();
        $count = count($cars);
        $io->progressStart($count);

        foreach ($cars as $car) {
            $car->updateHash();
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('Updated hashes for %d cars.', $count));

        return Command::SUCCESS;
    }
}
