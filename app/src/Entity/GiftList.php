<?php

namespace App\Entity;

use App\Repository\GiftListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ORM\Entity(repositoryClass: GiftListRepository::class)]
class GiftList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getGift", "userList", "userDetail"])]
    private ?int $id = null;

    #[Groups(["getGift", "userDetail"])]
    #[ORM\OneToMany(mappedBy: 'giftList', targetEntity: Gift::class, cascade: ['remove'])]
    private Collection $gifts;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'giftList')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'giftList')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Event $event = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function __construct()
    {
        $this->gifts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Gift>
     */
    public function getGifts(): Collection
    {
        return $this->gifts;
    }

    public function addGift(Gift $gift): static
    {
        if (!$this->gifts->contains($gift)) {
            $this->gifts->add($gift);
            $gift->setGiftList($this);
        }

        return $this;
    }

    public function removeGift(Gift $gift): static
    {
        if ($this->gifts->removeElement($gift)) {
            // set the owning side to null (unless already changed)
            if ($gift->getGiftList() === $this) {
                $gift->setGiftList(null);
            }
        }

        return $this;
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

}
