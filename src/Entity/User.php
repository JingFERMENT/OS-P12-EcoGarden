<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse e-mail est déjà utilisée.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @var int|null The user ID
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUser"])]
    private ?int $id = null;

    /**
     * @var string|null The first name of the user
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le prénom doit avoir au moins {{ limit }} caractères.",
        maxMessage: "Le prénom ne peut pas dépasser {{ limit }} caractères. "
    )]
    #[Assert\Regex(
        pattern: "/^[A-Z][a-zA-Z'-]+$/",
        message: "Le prénom ne doit contenir que des lettres et commence par un majuscule."
    )]
    #[Groups(["getUser"])]
    private ?string $firstName = null;

    /**
     * @var string|null The last name of the user
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom doit avoir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères. "
    )]
    #[Assert\Regex(
        pattern: "/^[A-Z][a-zA-Z'-]+$/",
        message: "Le nom ne doit contenir que des lettres et commence par un majuscule."
    )]
    #[Groups(["getUser"])]
    private ?string $lastName = null;

    /**
     * @var string|null The user email
     */
    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L’adresse e-mail ne peut pas être vide.')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        message: 'L’adresse e-mail doit être au format valide.'
    )]
    #[Groups(["getUser"])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(["getUser"])]
    private array $roles = [];

    /**
     * @var string|null Mot de passe en clair (non persisté, utilisé uniquement lors de la création ou mise à jour)
     */
     #[Assert\NotBlank(message: 'Le mot de passe ne peut pas être vide.', groups: ['create'])]
    #[Assert\Length(
        min: 8,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
    )]
    private ?string $plainPassword = null;

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column]
    #[Groups(["getUser"])]
    private ?string $password = null;

    /**
     * @var string|null The city of the user
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La ville ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "La ville doit avoir au moins {{ limit }} caractères.",
        maxMessage: "La ville ne peut pas dépasser {{ limit }} caractères. "
    )]
    #[Groups(["getUser"])]
    private ?string $city = null;

    /**
     * @var Collection<int, Advice>
     */
    #[ORM\OneToMany(targetEntity: Advice::class, mappedBy: 'user')]
    private Collection $advice;

    public function __construct()
    {
        $this->advice = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Méthode getUsername qui permet de retourner le champ qui est utilisé pour l'authentification.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
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

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return Collection<int, Advice>
     */
    public function getAdvice(): Collection
    {
        return $this->advice;
    }

    public function addAdvice(Advice $advice): static
    {
        if (!$this->advice->contains($advice)) {
            $this->advice->add($advice);
            $advice->setUser($this);
        }

        return $this;
    }

    public function removeAdvice(Advice $advice): static
    {
        if ($this->advice->removeElement($advice)) {
            // set the owning side to null (unless already changed)
            if ($advice->getUser() === $this) {
                $advice->setUser(null);
            }
        }

        return $this;
    }
}
