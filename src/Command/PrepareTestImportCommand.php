<?php

namespace App\Command;

use App\Repository\CarRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:prepare-test-import',
    description: 'Generates test CSV data based on an existing car',
)]
class PrepareTestImportCommand extends Command
{
    public function __construct(
        private CarRepository $carRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $car = $this->carRepository->findOneBy([]); // Get first car

        if (!$car) {
            $io->error('No cars found in DB.');
            return Command::FAILURE;
        }

        $io->section('Car Details');
        $io->text('ID: ' . $car->getId());
        $io->text('Hash: ' . ($car->getHash() ?? 'NULL'));

        $properties = [
            'manufacturer',
            'model',
            'type',
            'model_from',
            'model_to',
            'body_type',
            'drive_type',
            'displacement_liters',
            'displacement_cmm',
            'fuel_type',
            'kw',
            'hp',
            'cylinders',
            'valves',
            'engine_type',
            'engine_codes',
            'kba',
            'text_value'
        ];

        $header = implode(',', $properties);
        $values = [];
        foreach ($properties as $prop) {
            $getter = 'get' . str_replace('_', '', ucwords($prop, '_'));
            $val = $car->$getter();
            if ($prop === 'model_to' && $val === null)
                $val = 'NULL';
            $values[] = $val;
        }
        $row = '"' . implode('","', $values) . '"';

        $io->section('Nested CSV Content (for "zastosowania" column)');
        $io->text("Values:\n" . $header . "\n" . $row);

        return Command::SUCCESS;
    }
}
