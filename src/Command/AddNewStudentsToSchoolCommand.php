<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AuthenticationRepository;
use App\Repository\SchoolRepository;
use App\Repository\UserRepository;
use App\Repository\UserRoleRepository;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use App\Service\Directory;

/**
 * Adds all the users in the directory to a school with the student role
 *
 * Class AddNewStudentsToSchoolCommand
 */
#[AsCommand(
    name: 'ilios:add-students',
    description: 'Add students found by a directory filter into a school.',
    aliases: ['ilios:directory:add-students']
)]
class AddNewStudentsToSchoolCommand extends Command
{
    public function __construct(
        protected UserRepository $userRepository,
        protected SchoolRepository $schoolRepository,
        protected AuthenticationRepository $authenticationRepository,
        protected UserRoleRepository $userRoleRepository,
        protected Directory $directory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'schoolId',
                InputArgument::REQUIRED,
                'Which school ID to add new students to.'
            )
            ->addArgument(
                'filter',
                InputArgument::REQUIRED,
                'An LDAP filter to use in finding students who belong to the school in the directory.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filter = $input->getArgument('filter');
        $schoolId = $input->getArgument('schoolId');
        $school = $this->schoolRepository->findOneBy(['id' => $schoolId]);
        if (!$school) {
            throw new Exception(
                "School with id {$schoolId} could not be found."
            );
        }
        $output->writeln("<info>Searching for new students to add to " . $school->getTitle() . ".</info>");

        $students = $this->directory->findByLdapFilter($filter);

        if (!$students) {
            $output->writeln("<error>{$filter} returned no results.</error>");
            return Command::SUCCESS;
        }
        $output->writeln('<info>Found ' . count($students) . ' students in the directory.</info>');

        $campusIds = $this->userRepository->getAllCampusIds();

        $newStudents = array_filter($students, fn(array $arr) => !in_array($arr['campusId'], $campusIds));

        if ($newStudents === []) {
            $output->writeln("<info>There are no new students to add.</info>");
            return Command::SUCCESS;
        }
        $output->writeln(
            '<info>There are ' .
            count($newStudents) .
            ' new students to be added to ' .
            $school->getTitle() .
            '.</info>'
        );
        $rows = array_map(fn(array $arr) => [
            $arr['campusId'],
            $arr['preferredFirstName'] ?? $arr['firstName'],
            $arr['preferredLastName'] ?? $arr['lastName'],
            $arr['email'],
        ], $newStudents);
        $table = new Table($output);
        $table->setHeaders(['Campus ID', 'First', 'Last', 'Email'])->setRows($rows);
        $table->render();

        $helper = $this->getHelper('question');
        $output->writeln('');
        $question = new ConfirmationQuestion(
            '<question>Do you wish to add these students to ' . $school->getTitle() . '? </question>' . "\n",
            true
        );

        if ($helper->ask($input, $output, $question)) {
            $studentRole = $this->userRoleRepository->findOneBy(['title' => 'Student']);
            foreach ($newStudents as $userRecord) {
                if (empty($userRecord['email'])) {
                    $output->writeln(
                        '<error>Unable to add student ' .
                        var_export($userRecord, true) .
                        ' they have no email address.</error>'
                    );
                    continue;
                }
                if (empty($userRecord['campusId'])) {
                    $output->writeln(
                        '<error>Unable to add student ' .
                        var_export($userRecord, true) .
                        ' they have no campus ID.</error>'
                    );
                    continue;
                }
                if (empty($userRecord['username'])) {
                    $output->writeln(
                        '<error>Unable to add student ' .
                        var_export($userRecord, true) .
                        ' they have no username.</error>'
                    );
                    continue;
                }
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
                $user->addRole($studentRole);
                $this->userRepository->update($user);

                $authentication = $this->authenticationRepository->create();
                $authentication->setUser($user);
                $authentication->setUsername($userRecord['username']);
                $this->authenticationRepository->update($authentication, false);

                $studentRole->addUser($user);
                $this->userRoleRepository->update($studentRole);

                $output->writeln(
                    '<info>Success! New student #' .
                    $user->getId() . ' ' .
                    $user->getFirstAndLastName() .
                    ' created.</info>'
                );
            }

            return Command::SUCCESS;
        } else {
            $output->writeln('<comment>Update canceled.</comment>');

            return Command::FAILURE;
        }
    }
}
