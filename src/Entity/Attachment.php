<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Attachment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Solicitation::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false)]
    private Solicitation $solicitation;

    #[ORM\Column(length: 255)]
    private string $originalName;

    #[ORM\Column(length: 255)]
    private string $storedPath;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getSolicitation(): Solicitation { return $this->solicitation; }
    public function setSolicitation(Solicitation $solicitation): self { $this->solicitation = $solicitation; return $this; }
    public function getOriginalName(): string { return $this->originalName; }
    public function setOriginalName(string $originalName): self { $this->originalName = $originalName; return $this; }
    public function getStoredPath(): string { return $this->storedPath; }
    public function setStoredPath(string $storedPath): self { $this->storedPath = $storedPath; return $this; }
    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): self { $this->mimeType = $mimeType; return $this; }
}
