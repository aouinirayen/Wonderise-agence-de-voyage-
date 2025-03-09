<?php

namespace App\Entity;

use App\Repository\ExperienceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExperienceRepository::class)]
class Experience
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Title is required to share your experience')]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: 'Title is too short! It must be at least {{ 10 }} characters',
        maxMessage: 'Title is too long! It cannot exceed {{ 255 }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s]+$/',
        message: 'Title can only contain letters, numbers, and spaces'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Share your story! Description cannot be empty')]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: 'Description must be at least {{ 20 }} characters long',
        maxMessage: 'Description cannot exceed {{ 500 }} characters'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'Please enter a valid URL')]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Location is required')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Location name is too long'
    )]
    private ?string $lieu = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Category name is required')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Category name is too long'
    )]
    private ?string $categorie = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: 'ID is a required field')]
    private ?string $id_client = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: 'Please specify the date of your experience')]
    #[Assert\Type(
        type: "\DateTimeInterface",
        message: 'Date is not in a valid format'
    )]
    private ?\DateTimeInterface $date = null;
    
    #[Assert\NotBlank(message: 'Please specify the date of your experience')]
    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private $airQualityData = [];

    #[ORM\OneToMany(mappedBy: 'experience', targetEntity: Commentaire::class, orphanRemoval: true)]
    private Collection $commentaires;

    #[ORM\OneToMany(mappedBy: 'experience', targetEntity: Rating::class, orphanRemoval: true)]
    private Collection $ratings;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getIdClient(): ?string
    {
        return $this->id_client;
    }

    public function setIdClient(string $id_client): static
    {
        $this->id_client = $id_client;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getAirQualityData(): ?array
    {
        return $this->airQualityData;
    }

    public function setAirQualityData(?array $airQualityData): self
    {
        $this->airQualityData = $airQualityData;
        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setExperience($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getExperience() === $this) {
                $commentaire->setExperience(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): self
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setExperience($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): self
    {
        if ($this->ratings->removeElement($rating)) {
            if ($rating->getExperience() === $this) {
                $rating->setExperience(null);
            }
        }

        return $this;
    }

    public function getAverageRating(): float
    {
        if ($this->ratings->isEmpty()) {
            return 0;
        }

        $sum = 0;
        foreach ($this->ratings as $rating) {
            $sum += $rating->getValue();
        }

        return $sum / $this->ratings->count();
    }
}
