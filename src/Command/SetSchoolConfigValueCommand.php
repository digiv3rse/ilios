<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\SchoolConfig;
use App\Entity\SchoolInterface;
use App\Repository\SchoolConfigRepository;
use App\Repository\SchoolRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Set a school configuration value in the DB
 */
#[AsCommand(
    name: 'ilios:set-school-config-value',
    description: 'Set a school configuration value in the DB',
    aliases: ['ilios:maintenance:set-school-config-value'],
)]
class SetSchoolConfigValueCommand extends Command
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected SchoolConfigRepository $schoolConfigRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            //required arguments
            ->addArgument(
                'school',
                InputArgument::REQUIRED,
                'The id of the school the config belongs to'
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name of the configuration we are setting'
            )
            ->addArgument(
                'value',
                InputArgument::REQUIRED,
                'The value of the configuration we are setting'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schoolId = $input->getArgument('school');
        $name = $input->getArgument('name');
        $value = $input->getArgument('value');

        $school = $this->schoolRepository->findOneBy(['id' => $schoolId]);
        if (!$school) {
            $output->writeln("<error>There are no schools with id {$schoolId}.</error>");
            return Command::FAILURE;
        }

        $config = $this->schoolConfigRepository->findOneBy(['school' => $school->getId(), 'name' => $name]);
        if (!$config) {
            $config = $this->schoolConfigRepository->create();
            $config->setName($name);
            $config->setSchool($school);
        }
        $config->setValue($value);

        $this->schoolConfigRepository->update($config, true);

        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }
}
