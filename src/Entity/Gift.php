<?php

namespace App\Entity;

use App\Repository\GiftRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: GiftRepository::class)]
class Gift
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("getGifts")]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups("getGifts")]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'gifts')]
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
