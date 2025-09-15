<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class EventUpdateDto
{
    /**
     * Nom de l'événement
     */
    #[
        Assert\NotBlank(
            message: "Le nom de l'événement ne peut pas être vide",
            groups: ["name_validation"],
        ),
    ]
    #[
        Assert\Length(
            max: 255,
            maxMessage: "Le nom de l'événement ne peut pas dépasser {{ limit }} caractères",
            groups: ["name_validation"],
        ),
    ]
    #[
        Assert\Type(
            type: "string",
            message: "Le nom doit être une chaîne de caractères",
            groups: ["name_validation"],
        ),
    ]
    #[
        Assert\Regex(
            pattern: '/^[a-zA-Z0-9\s\-_À-ÿ]+$/',
            message: "Le nom de l'événement contient des caractères non autorisés",
            groups: ["name_validation"],
        ),
    ]
    public ?string $name = null;

    /**
     * ID de l'organisateur (optionnel, seulement pour les administrateurs)
     */
    #[
        Assert\Type(
            type: "integer",
            message: "L'ID de l'organisateur doit être un entier",
            groups: ["organizer_validation"],
        ),
    ]
    #[
        Assert\Positive(
            message: "L'ID de l'organisateur doit être positif",
            groups: ["organizer_validation"],
        ),
    ]
    public ?int $organizerId = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    public function getOrganizerId(): ?int
    {
        return $this->organizerId;
    }

    public function setOrganizerId(?int $organizerId): self
    {
        $this->organizerId = $organizerId;
        return $this;
    }

    /**
     * Vérifie si au moins un champ est fourni pour la mise à jour
     */
    #[
        Assert\IsTrue(
            message: "Au moins un champ doit être fourni pour la mise à jour",
            groups: ["update_validation"],
        ),
    ]
    public function isValidUpdate(): bool
    {
        return $this->name !== null || $this->organizerId !== null;
    }
}
