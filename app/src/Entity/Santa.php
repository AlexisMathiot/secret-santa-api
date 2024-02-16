<?php

namespace App\Entity;

use App\Repository\SantaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SantaRepository::class)]
class Santa
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'santas')]
    private ?Event $event = null;

    #[ORM\ManyToOne(inversedBy: 'santas')]
    private ?User $santa = null;

    #[ORM\ManyToOne(inversedBy: 'userSantas')]
    private ?User $user;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function setSanta(?User $santa): static
    {
        $this->santa = $santa;
        return $this;
    }

    public function getSanta(): ?User
    {
        return $this->santa;
    }

}
