<?php

namespace App\Entity;

use App\Repository\AdviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "conseilParId",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups={"getAdvice"})
 * )
 *
 * 
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "supprimerUnConseil",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups={"getAdvice"}, excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 *
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "mettreAJourUnConseil",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups={"getAdvice"}, excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 *
 */
#[ORM\Entity(repositoryClass: AdviceRepository::class)]
class Advice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getAdvice"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAdvice"])]
    #[Assert\NotBlank(message: 'La description ne peut pas être vide.')]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'advice')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Utilisateur ne peut pas être vide.')]
    private ?User $user = null;

    /**
     * @var Collection<int, Month>
     */
    #[ORM\ManyToMany(targetEntity: Month::class, inversedBy: 'advices')]
    #[Assert\Count(
        min: 1,
        minMessage: 'Vous devez sélectionner au moins un mois.',
        max: 12,
        maxMessage: 'Vous ne pouvez pas sélectionner plus de 12 mois.'
    )]
    private Collection $months;

    public function __construct()
    {
        $this->months = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Months>
     */
    public function getMonth(): Collection
    {
        return $this->months;
    }

    public function addMonth(Month $month): static
    {
        if (!$this->months->contains($month)) {
            $this->months->add($month);
        }

        return $this;
    }

    public function removeMonth(Month $month): static
    {
        $this->months->removeElement($month);

        return $this;
    }
}
