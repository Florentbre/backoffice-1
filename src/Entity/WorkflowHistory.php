<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class WorkflowHistory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Solicitation::class)]
    private Solicitation $solicitation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $actor = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $fromStatus = null;

    #[ORM\Column(length: 40)]
    private string $toStatus;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }
    public function setSolicitation(Solicitation $solicitation): self { $this->solicitation = $solicitation; return $this; }
    public function setActor(?User $actor): self { $this->actor = $actor; return $this; }
    public function setFromStatus(?string $fromStatus): self { $this->fromStatus = $fromStatus; return $this; }
    public function setToStatus(string $toStatus): self { $this->toStatus = $toStatus; return $this; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getFromStatus(): ?string { return $this->fromStatus; }
    public function getToStatus(): string { return $this->toStatus; }
    public function getNote(): ?string { return $this->note; }
    public function getActor(): ?User { return $this->actor; }
}
