<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $username;

    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column(length: 100)]
    private string $service;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }
    public function getUserIdentifier(): string { return $this->username; }
    public function getRoles(): array { return array_unique($this->roles); }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }
    public function eraseCredentials(): void {}
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getService(): string { return $this->service; }
    public function setService(string $service): self { $this->service = $service; return $this; }
}
