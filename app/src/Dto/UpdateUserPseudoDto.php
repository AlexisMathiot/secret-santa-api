<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

/**
 * DTO pour la mise à jour du pseudo utilisateur
 * Endpoint: PUT /api/user/{id}
 */
#[
    OA\Schema(
        schema: "UpdateUserPseudoDto",
        description: "Données pour la mise à jour du pseudo utilisateur",
        type: "object",
        required: ["pseudo"],
    ),
]
class UpdateUserPseudoDto
{
    #[Assert\NotBlank(message: "Le pseudo ne peut pas être vide")]
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
    #[
        OA\Property(
            property: "pseudo",
            description: "Nouveau pseudo de l'utilisateur",
            type: "string",
            minLength: 2,
            maxLength: 50,
            pattern: "^[a-zA-Z0-9_-]+$",
            example: "nouveau_pseudo",
        ),
    ]
    private string $pseudo;

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): self
    {
        $this->pseudo = $pseudo;
        return $this;
    }
}
