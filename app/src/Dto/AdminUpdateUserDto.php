<?php

namespace App\Dto;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la mise à jour complète d'un utilisateur par l'admin
 * Endpoint: PUT /api/admin/user/{id}
 */
#[
    OA\Schema(
        schema: "AdminUpdateUserDto",
        description: "Données pour la mise à jour complète d'un utilisateur par l'administrateur",
        type: "object",
    ),
]
class AdminUpdateUserDto
{
    #[Assert\Email(message: 'L\'email doit être valide')]
    #[
        Assert\Length(
            max: 180,
            maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères',
        ),
    ]
    #[
        OA\Property(
            property: "email",
            description: "Nouvelle adresse email de l'utilisateur (optionnel)",
            type: "string",
            format: "email",
            maxLength: 180,
            nullable: true,
            example: "nouveau.email@example.com",
        ),
    ]
    private ?string $email = null;

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
    #[
        OA\Property(
            property: "password",
            description: "Nouveau mot de passe (optionnel, min 8 caractères avec majuscule, minuscule et chiffre)",
            type: "string",
            format: "password",
            minLength: 8,
            maxLength: 255,
            nullable: true,
            example: "nouveauMotDePasse123!",
        ),
    ]
    private ?string $password = null;

    #[
        OA\Property(
            property: "pseudo",
            description: "Nouveau pseudo de l'utilisateur (optionnel)",
            type: "string",
            minLength: 2,
            maxLength: 50,
            pattern: "^[a-zA-Z0-9_-]+$",
            nullable: true,
            example: "nouveau_pseudo",
        ),
    ]
    #[
        Assert\Length(
            min: 2,
            max: 50,
            minMessage: "Le pseudo doit contenir au moins {{ limit }} caractères",
            maxMessage: "Le pseudo ne peut pas dépasser {{ limit }} caractères",
        ),
    ]
    #[
        Assert\Regex(
            pattern: '/^[a-zA-Z0-9_-]+$/',
            message: "Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores",
        ),
    ]
    private ?string $pseudo = null;

    #[
        OA\Property(
            property: "roles",
            description: "Nouveaux rôles de l'utilisateur (optionnel)",
            type: "array",
            items: new OA\Items(type: "string"),
            nullable: true,
            example: ["ROLE_USER", "ROLE_ADMIN"],
        ),
    ]
    private ?array $roles = null;

    // Getters et Setters
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): self
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Applique les modifications à l'entité User
     * Met à jour seulement les champs non-null du DTO
     */
    public function applyToUser($user): void
    {
        if ($this->email !== null) {
            $user->setEmail($this->email);
        }

        if ($this->pseudo !== null) {
            $user->setPseudo($this->pseudo);
        }

        if ($this->roles !== null) {
            $user->setRoles($this->roles);
        }

        // Le mot de passe est géré séparément dans le contrôleur pour le hashage
    }

    /**
     * Vérifie si au moins un champ est renseigné
     */
    public function hasAnyField(): bool
    {
        return $this->email !== null ||
            $this->password !== null ||
            $this->firstname !== null ||
            $this->lastname !== null ||
            $this->pseudo !== null ||
            $this->roles !== null;
    }
}
