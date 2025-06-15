<?php

namespace App\Entity;

use App\Repository\MonthRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MonthRepository::class)]
class Month
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Advice>
     */
    #[ORM\ManyToMany(targetEntity: Advice::class, mappedBy: 'months')]
    private Collection $advices;

    public function __construct()
    {
        $this->advices = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Advice>
     */
    public function getAdvice(): Collection
    {
        return $this->advices;
    }

    public function addAdvice(Advice $advice): static
    {
        if (!$this->advices->contains($advice)) {
            $this->advices->add($advice);
            $advice->addMonth($this);
        }

        return $this;
    }

    public function removeAdvice(Advice $advice): static
    {
        if ($this->advices->removeElement($advice)) {
            $advice->removeMonth($this);
        }

        return $this;
    }
}
