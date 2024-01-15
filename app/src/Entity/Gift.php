<?php

namespace App\Entity;

use App\Repository\GiftRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GiftRepository::class)]
class Gift
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getGift", "userDetail"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getGift", "userDetail"])]
    #[Assert\NotBlank(message: "Le nom du cadeau est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom doit faire au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'gifts')]
    #[ORM\JoinColumn(name: "gift_list_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private ?GiftList $giftList = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getGiftList(): ?GiftList
    {
        return $this->giftList;
    }

    public function setGiftList(?GiftList $giftList): static
    {
        $this->giftList = $giftList;

        return $this;
    }
}
