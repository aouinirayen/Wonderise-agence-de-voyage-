<?php

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['country:read', 'monument:read', 'food:read', 'art:read', 'celebrity:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['country:read', 'monument:read', 'food:read', 'art:read', 'celebrity:read'])]
    #[Assert\NotBlank(message: 'The country name cannot be blank')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'The country name must be at least {{ limit }} characters long',
        maxMessage: 'The country name cannot be longer than {{ limit }} characters'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['country:read'])]
    private ?string $img = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['country:read'])]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'The description cannot be longer than {{ limit }} characters'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['country:read'])]
    #[Assert\NotBlank(message: 'The country currency cannot be blank')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'The country currency must be at least {{ limit }} characters long',
        maxMessage: 'The country currrency cannot be longer than {{ limit }} characters'
    )]
    private ?string $currency = null;

    #[ORM\Column(length: 2, nullable: true)]
    #[Groups(['country:read'])]
    #[Assert\NotBlank(message: 'The country isoCode cannot be blank')]
    private ?string $isoCode = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(['country:read'])]
    #[Assert\NotBlank(message: 'The country callingCode cannot be blank')]
    #[Assert\Regex(
        pattern: '/^\+\d{1,4}$/',
        message: 'The calling code must be in format +XXX'
    )]
    private ?string $callingCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['country:read'])]
    #[Assert\NotBlank(message: 'The country climate cannot be blank')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'The climate description cannot be longer than {{ limit }} characters'
    )]
    private ?string $climate = null;

    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Monument::class, cascade: ['persist', 'remove'])]
    #[Groups(['country:read'])]
    private Collection $monuments;

    #[ORM\OneToMany(mappedBy: 'country', targetEntity: TraditionalFood::class, cascade: ['persist', 'remove'])]
    #[Groups(['country:read'])]
    private Collection $traditionalFoods;

    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Art::class, cascade: ['persist', 'remove'])]
    #[Groups(['country:read'])]
    private Collection $arts;

    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Selebrity::class, cascade: ['persist', 'remove'])]
    #[Groups(['country:read'])]
    private Collection $celebrities;

    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Rating::class, cascade: ['remove'])]
    private Collection $ratings;

    public function __construct()
    {
        $this->monuments = new ArrayCollection();
        $this->traditionalFoods = new ArrayCollection();
        $this->arts = new ArrayCollection();
        $this->celebrities = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): self
    {
        $this->img = $img;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getIsoCode(): ?string
    {
        return $this->isoCode;
    }

    public function setIsoCode(?string $isoCode): self
    {
        $this->isoCode = $isoCode;
        return $this;
    }

    public function getCallingCode(): ?string
    {
        return $this->callingCode;
    }

    public function setCallingCode(?string $callingCode): self
    {
        $this->callingCode = $callingCode;
        return $this;
    }

    public function getClimate(): ?string
    {
        return $this->climate;
    }

    public function setClimate(?string $climate): self
    {
        $this->climate = $climate;
        return $this;
    }

    /**
     * @return Collection<int, Monument>
     */
    public function getMonuments(): Collection
    {
        return $this->monuments;
    }

    public function addMonument(Monument $monument): self
    {
        if (!$this->monuments->contains($monument)) {
            $this->monuments->add($monument);
            $monument->setCountry($this);
        }

        return $this;
    }

    public function removeMonument(Monument $monument): self
    {
        if ($this->monuments->removeElement($monument)) {
            // set the owning side to null (unless already changed)
            if ($monument->getCountry() === $this) {
                $monument->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TraditionalFood>
     */
    public function getTraditionalFoods(): Collection
    {
        return $this->traditionalFoods;
    }

    public function addTraditionalFood(TraditionalFood $traditionalFood): self
    {
        if (!$this->traditionalFoods->contains($traditionalFood)) {
            $this->traditionalFoods->add($traditionalFood);
            $traditionalFood->setCountry($this);
        }

        return $this;
    }

    public function removeTraditionalFood(TraditionalFood $traditionalFood): self
    {
        if ($this->traditionalFoods->removeElement($traditionalFood)) {
            // set the owning side to null (unless already changed)
            if ($traditionalFood->getCountry() === $this) {
                $traditionalFood->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Art>
     */
    public function getArts(): Collection
    {
        return $this->arts;
    }

    public function addArt(Art $art): self
    {
        if (!$this->arts->contains($art)) {
            $this->arts->add($art);
            $art->setCountry($this);
        }

        return $this;
    }

    public function removeArt(Art $art): self
    {
        if ($this->arts->removeElement($art)) {
            // set the owning side to null (unless already changed)
            if ($art->getCountry() === $this) {
                $art->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Selebrity>
     */
    public function getCelebrities(): Collection
    {
        return $this->celebrities;
    }

    public function addCelebrity(Selebrity $celebrity): self
    {
        if (!$this->celebrities->contains($celebrity)) {
            $this->celebrities->add($celebrity);
            $celebrity->setCountry($this);
        }

        return $this;
    }

    public function removeCelebrity(Selebrity $celebrity): self
    {
        if ($this->celebrities->removeElement($celebrity)) {
            // set the owning side to null (unless already changed)
            if ($celebrity->getCountry() === $this) {
                $celebrity->setCountry(null);
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
            $rating->setCountry($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): self
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getCountry() === $this) {
                $rating->setCountry(null);
            }
        }

        return $this;
    }
}