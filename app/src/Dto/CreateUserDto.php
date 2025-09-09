<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la création d'un utilisateur
 * Endpoint: POST /api/signin
 */
class CreateUserDto
{
    #[Assert\NotBlank(message: 'L\'email ne peut pas être vide')]
    #[Assert\Email(message: 'L\'email doit être valide')]
    #[
        Assert\Length(
            max: 180,
            maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères',
        ),
    ]
    private string $email;

    #[Assert\NotBlank(message: "Le mot de passe ne peut pas être vide")]
    #[
        Assert\Length(
            min: 8,
            max: 255,
            minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères",
            maxMessage: "Le mot de passe ne peut pas dépasser {{ limit }} caractères",
        ),
    ]
    #[
        Assert\Regex(
            pattern: "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/",
            message: "Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre",
        ),
    ]
    private string $password;

    #[
        Assert\Regex(
            pattern: '/^[a-zA-Z0-9_-]+$/',
            message: "Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores",
        ),
    ]
    private ?string $pseudo = null;

    #[Assert\NotBlank(message: "Le nom d'utilisateur est obligatoire")]
    private string $username;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): self
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Convertit le DTO en entité User
     */
    public function toUser(): array
    {
        return [
            "email" => $this->email,
            "password" => $this->password,
            "pseudo" => $this?->pseudo,
            "username" => $this->username,
        ];
    }
}
