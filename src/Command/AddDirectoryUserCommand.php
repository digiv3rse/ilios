<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AuthenticationRepository;
use App\Repository\SchoolRepository;
use App\Repository\UserRepository;
use App\Service\Directory;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Add a user by looking them up in the directory
 *
 * Class AddDirectoryUserCommand
 */
#[AsCommand(
    name: 'ilios:add-directory-user',
    description: 'Add a user to ilios.',
    aliases: ['ilios:directory:add-user']
)]
class AddDirectoryUserCommand extends Command
{
    public function __construct(
        protected UserRepository $userRepository,
        protected AuthenticationRepository $authenticationRepository,
        protected SchoolRepository $schoolRepository,
        protected Directory $directory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'campusId',
                InputArgument::REQUIRED,
                'The campus ID to lookup for adding the new user.'
            )
            ->addArgument(
                'schoolId',
                InputArgument::REQUIRED,
                'The primary school of the new user.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $campusId = $input->getArgument('campusId');
        $user = $this->userRepository->findOneBy(['campusId' => $campusId]);
        if ($user) {
            throw new Exception(
                'User #' . $user->getId() . " with campus id {$campusId} already exists."
            );
        }
        $schoolId = $input->getArgument('schoolId');
        $school = $this->schoolRepository->findOneBy(['id' => $schoolId]);
        if (!$school) {
            throw new Exception(
                "School with id {$schoolId} could not be found."
            );
        }

        $userRecord = $this->directory->findByCampusId($campusId);

        if (!$userRecord) {
            $output->writeln("<error>Unable to find campus ID {$campusId} in the directory.</error>");
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Campus ID', 'First', 'Last', 'Display', 'Email', 'Username', 'Phone Number'])
            ->setRows([
                [
                    $userRecord['campusId'],
                    $userRecord['preferredFirstName'] ?? $userRecord['firstName'],
                    $userRecord['preferredLastName'] ?? $userRecord['lastName'],
                    $userRecord['displayName'],
                    $userRecord['email'],
                    $userRecord['username'],
                    $userRecord['telephoneNumber'],
                ],
            ])
        ;
        $table->render();

        $helper = $this->getHelper('question');
        $output->writeln('');
        $question = new ConfirmationQuestion(
            "<question>Do you wish to add this user to Ilios?</question>\n",
            true
        );

        if ($helper->ask($input, $output, $question)) {
            $user = $this->userRepository->create();
            $user->setFirstName($userRecord['preferredFirstName'] ?? $userRecord['firstName']);
            $user->setLastName($userRecord['preferredLastName'] ?? $userRecord['lastName']);
            $user->setDisplayName($userRecord['displayName']);
            $user->setEmail($userRecord['email']);
            $user->setCampusId($userRecord['campusId']);
            $user->setAddedViaIlios(true);
            $user->setEnabled(true);
            $user->setSchool($school);
            $user->setUserSyncIgnore(false);
            $this->userRepository->update($user);

            $authentication = $this->authenticationRepository->create();
            $authentication->setUser($user);
            $authentication->setUsername($userRecord['username']);
            $this->authenticationRepository->update($authentication);

            $output->writeln(
                '<info>Success! New user #' . $user->getId() . ' ' . $user->getFirstAndLastName() . ' created.</info>'
            );
        } else {
            $output->writeln('<comment>Canceled.</comment>');
        }

        return Command::SUCCESS;
    }
}
