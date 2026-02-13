<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:bootstrap')]
class BootstrapCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = [
            ['agent1', 'agent1', 'agent1@internal.local', 'Support', ['ROLE_USER']],
            ['manager1', 'manager1', 'manager1@internal.local', 'Pilotage', ['ROLE_USER', 'ROLE_MANAGER']],
            ['legal1', 'legal1', 'legal1@internal.local', 'Juridique', ['ROLE_USER']],
        ];

        foreach ($users as [$username, $password, $email, $service, $roles]) {
            if ($this->em->getRepository(User::class)->findOneBy(['username' => $username])) {
                continue;
            }
            $u = (new User())
                ->setUsername($username)
                ->setEmail($email)
                ->setService($service)
                ->setRoles($roles);
            $u->setPassword($this->hasher->hashPassword($u, $password));
            $this->em->persist($u);
        }

        $this->em->flush();
        $output->writeln('Bootstrap terminé.');

        return Command::SUCCESS;
    }
}
