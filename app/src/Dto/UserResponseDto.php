<?php

namespace App\Dto;

/**
 * DTO pour la réponse utilisateur (après création ou mise à jour)
 */
class UserResponseDto
{
    private int $id;
    private string $email;
    private ?string $pseudo;
    private array $roles;
    private \DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    public function __construct(
        int $id,
        string $email,
        ?string $pseudo,
        array $roles = [],
        \DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->pseudo = $pseudo;
        $this->roles = $roles;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Créer un UserResponseDto à partir d'une entité User
     */
    public static function fromUser($user): self
    {
        return new self(
            $user->getId(),
            $user->getEmail(),
            $user->getPseudo(),
            $user->getRoles(),
            $user->getCreatedAt(),
            $user->getUpdatedAt(),
        );
    }
}
