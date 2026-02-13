<?php

namespace App\Service;

use App\Entity\Solicitation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class IncomingJsonImporter
{
    public function __construct(private EntityManagerInterface $em, private string $incomingDir)
    {
    }

    public function import(): void
    {
        $defaultAssignee = $this->em->getRepository(User::class)->findOneBy([]);
        foreach (glob($this->incomingDir . '/*.json') ?: [] as $file) {
            $payload = json_decode((string) file_get_contents($file), true);
            if (!is_array($payload) || empty($payload['external_id']) || empty($payload['title'])) {
                rename($file, $file . '.invalid');
                continue;
            }
            if ($this->em->getRepository(Solicitation::class)->findOneBy(['externalId' => $payload['external_id']])) {
                rename($file, $file . '.processed');
                continue;
            }

            $sol = (new Solicitation())
                ->setExternalId($payload['external_id'])
                ->setTitle($payload['title'])
                ->setDescription($payload['description'] ?? null)
                ->setRequesterEmail($payload['requester_email'] ?? null)
                ->setPayload($payload)
                ->setAssignedTo($defaultAssignee);
            $this->em->persist($sol);
            $this->em->flush();
            rename($file, $file . '.processed');
        }
    }
}
