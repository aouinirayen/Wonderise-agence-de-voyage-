<?php

namespace App\Entity;

use App\Repository\SelebrityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SelebrityRepository::class)]
class Selebrity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['celebrity:read', 'country:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['celebrity:read', 'country:read'])]
    #[Assert\NotBlank(message: "Name cannot be empty")]
    #[Assert\NotNull(message: "Name is required")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Name must be at least {{ limit }} characters long",
        maxMessage: "Name cannot be longer than {{ limit }} characters"
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['celebrity:read', 'country:read'])]
    #[Assert\NotBlank(message: "Work cannot be empty")]
    #[Assert\NotNull(message: "Work is required")]
    private ?string $work = null;

    #[ORM\Column(length: 255)]
    #[Groups(['celebrity:read', 'country:read'])]
   
    private ?string $img = null;

    #[ORM\Column(type: "text")]
    #[Groups(['celebrity:read', 'country:read'])]
    #[Assert\NotBlank(message: "Description cannot be empty")]
    #[Assert\NotNull(message: "Description is required")]
    #[Assert\Length(
        min: 10,
        minMessage: "Description must be at least {{ limit }} characters long"
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['celebrity:read', 'country:read'])]
    #[Assert\NotBlank(message: "Job cannot be empty")]
    #[Assert\NotNull(message: "Job is required")]
    private ?string $job = null;

    #[ORM\ManyToOne(inversedBy: 'celebrities')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['celebrity:read'])]
    #[Assert\NotBlank(message: "Country cannot be empty")]
    #[Assert\NotNull(message: "Country is required")]
    private ?Country $country = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['celebrity:read', 'country:read'])]
    #[Assert\NotBlank(message: "Date of birth cannot be empty")]
    #[Assert\NotNull(message: "Date of birth is required")]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(length: 255)]
    #[Groups(['celebrity:read', 'country:read'])]
    #[Assert\NotBlank(message: "Nationality cannot be empty")]
    #[Assert\NotNull(message: "Nationality is required")]
    private ?string $nationality = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['celebrity:read', 'country:read'])]
    #[Assert\NotBlank(message: "Notable works cannot be empty")]
    #[Assert\NotNull(message: "Notable works is required")]
    private ?string $notableWorks = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['celebrity:read', 'country:read'])]
    #[Assert\NotBlank(message: "Personal life information cannot be empty")]
    #[Assert\NotNull(message: "Personal life information is required")]
    private ?string $personalLife = null;

    #[ORM\Column(type: 'float')]
    #[Groups(['celebrity:read', 'country:read'])]
    #[Assert\NotBlank(message: "Net worth cannot be empty")]
    #[Assert\NotNull(message: "Net worth is required")]
    #[Assert\PositiveOrZero(message: "Net worth cannot be negative")]
    private ?float $netWorth = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getWork(): ?string
    {
        return $this->work;
    }

    public function setWork(?string $work): static
    {
        $this->work = $work;

        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): static
    {
        $this->img = $img;

        return $this;
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

    public function getJob(): ?string
    {
        return $this->job;
    }

    public function setJob(?string $job): static
    {
        $this->job = $job;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): static
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): static
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getNotableWorks(): ?string
    {
        return $this->notableWorks;
    }

    public function setNotableWorks(?string $notableWorks): static
    {
        $this->notableWorks = $notableWorks;

        return $this;
    }

    public function getPersonalLife(): ?string
    {
        return $this->personalLife;
    }

    public function setPersonalLife(?string $personalLife): static
    {
        $this->personalLife = $personalLife;

        return $this;
    }

    public function getNetWorth(): ?float
    {
        return $this->netWorth;
    }

    public function setNetWorth(?float $netWorth): static
    {
        $this->netWorth = $netWorth;

        return $this;
    }
}