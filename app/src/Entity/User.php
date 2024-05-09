<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[Groups("userDetail")]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Groups(['eventDetail', 'userList'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(["userList", "eventDetail", "usersInvitToEvent"])]
    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "Le nom d'utilisateur est obligatoire")]
    private ?string $username = null;

    #[Groups(["userList", "eventDetail", "usersInvitToEvent"])]
    #[ORM\Column(length: 180, unique: true, nullable: true)]
    private ?string $email = null;

    #[Groups(["userList", "eventDetail"])]
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var null|string The hashed password
     */
    #[Groups("userList")]
    #[ORM\Column]
    private ?string $password = null;

    #[Groups("userList")]
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: GiftList::class, cascade: ['persist', 'remove'])]
    private Collection|null $giftList = null;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'users')]
    private Collection $events;

    #[ORM\OneToMany(mappedBy: 'organizer', targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: ['cascade'])]
    private Collection $eventsOrganize;

    #[Groups("userResetPassword")]
    #[ORM\Column(nullable: true)]
    private ?string $resetToken = null;

    #[ORM\OneToMany(mappedBy: 'santa', targetEntity: Santa::class)]
    private Collection $santas;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Santa::class)]
    private Collection $userSantas;

    /**
     * @var null|DateTime
     */
    #[Groups("userResetPassword")]
    #[Assert\DateTime]
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?DateTime $timeSendResetPasswordLink = null;


    public function __construct()
    {
        $this->giftList = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->eventsOrganize = new ArrayCollection();
        $this->santas = new ArrayCollection();
        $this->userSantas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->username;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return User
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, GiftList>
     */
    public function getGiftList(): Collection
    {
        return $this->giftList;
    }

    public function addGiftList(GiftList $giftList): static
    {
        if (!$this->giftList->contains($giftList)) {
            $this->giftList->add($giftList);
            $giftList->setUser($this);
        }

        return $this;
    }

    public function removeGiftList(GiftList $giftList): static
    {
        if ($this->giftList->removeElement($giftList)) {
            if ($giftList->getUser() === $this) {
                $giftList->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEventsOrganize(): Collection
    {
        return $this->eventsOrganize;
    }

    public function addEventsOrganize(Event $eventsOrganize): static
    {
        if (!$this->eventsOrganize->contains($eventsOrganize)) {
            $this->eventsOrganize->add($eventsOrganize);
            $eventsOrganize->setOrganizer($this);
        }

        return $this;
    }

    public function removeEventsOrganize(Event $eventsOrganize): static
    {
        if ($this->eventsOrganize->removeElement($eventsOrganize)) {
            // set the owning side to null (unless already changed)
            if ($eventsOrganize->getOrganizer() === $this) {
                $eventsOrganize->setOrganizer(null);
            }
        }

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    /**
     * @return Collection<int, Santa>
     */
    public function getSantas(): Collection
    {
        return $this->santas;
    }

    public function addSanta(Santa $santa): static
    {
        if (!$this->santas->contains($santa)) {
            $this->santas->add($santa);
            $santa->setSanta($this);
        }

        return $this;
    }

    public function removeSanta(Santa $santa): static
    {
        if ($this->santas->removeElement($santa)) {
            // set the owning side to null (unless already changed)
            if ($santa->getSanta() === $this) {
                $santa->setSanta(null);
            }
        }

        return $this;
    }

    public function addUserSanta(Santa $santa): static
    {
        if (!$this->userSantas->contains($santa)) {
            $this->userSantas->add($santa);
            $santa->setUser($this);
        }

        return $this;
    }

    public function getTimeSendResetPasswordLink(): ?DateTime
    {
        return $this->timeSendResetPasswordLink;
    }

    public function setTimeSendResetPasswordLink(?DateTime $timeSendResetPasswordLink): void
    {
        $this->timeSendResetPasswordLink = $timeSendResetPasswordLink;
    }
}
