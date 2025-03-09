<?php
namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'événement est obligatoire')]
    #[Assert\Length(
        min: 5,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    #[Assert\GreaterThanOrEqual(
        value: 'today',
        message: 'La date doit être à partir d\'aujourd\'hui'
    )]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: 'L\'heure est obligatoire')]
    private ?\DateTimeInterface $heure = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le lieu doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le lieu ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $lieu = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre de places est obligatoire')]
    #[Assert\Type(
        type: 'integer',
        message: 'Le nombre de places doit être un nombre entier'
    )]
    #[Assert\Range(
        min: 1,
        max: 2000,
        notInRangeMessage: 'Le nombre de places doit être entre {{ min }} et {{ max }}'
    )]
    private ?int $placeMax = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le prix est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif ou nul')]
    #[Assert\LessThan(
        value: 10000,
        message: 'Le prix ne peut pas dépasser {{ compared_value }} €'
    )]
    private ?float $prix = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    #[Assert\Choice(
        choices: ['actif', 'termine', 'annule'],
        message: 'Le statut doit être soit actif, termine ou annule'
    )]
    private ?string $status = 'actif';

    #[ORM\Column]
    private ?bool $isAnnule = false;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire')]
    #[Assert\Choice(
        choices: ['France', 'Espagne', 'Italie', 'Allemagne', 'Royaume-Uni', 'Portugal', 'Grèce', 'Suisse', 'Belgique', 'Pays-Bas', 'Tunisie', 'Maroc', 'Egypte', 'Bali', 'Brésil'],
        message: 'Veuillez choisir un pays valide'
    )]
    private ?string $pays = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire')]
    #[Assert\Choice(
        choices: ['festival', 'theatre', 'cinema', 'roadtrip', 'camping', 'beachparty'],
        message: 'Veuillez choisir une catégorie valide'
    )]
    private ?string $categorie = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isInterested = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isLiked = false;

    #[ORM\Column(type: 'integer')]
    private int $likesCount = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isFavorite = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?float $longitude = null;

    #[ORM\ManyToOne(targetEntity: Guide::class, inversedBy: "evenements")]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un guide doit être assigné à l\'événement')]
    private ?Guide $guide = null;

    #[ORM\OneToMany(mappedBy: "evenement", targetEntity: Commentaire::class, cascade: ["persist", "remove"])]
    private Collection $commentaires;

    public const STATUS_ACTIF = 'actif';
    public const STATUS_TERMINE = 'termine';
    public const STATUS_ANNULE = 'annule';

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
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

    public function getHeure(): ?\DateTimeInterface
    {
        return $this->heure;
    }

    public function setHeure(?\DateTimeInterface $heure): self
    {
        $this->heure = $heure;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
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

    public function getGuide(): ?Guide
    {
        return $this->guide;
    }

    public function setGuide(?Guide $guide): static
    {
        $this->guide = $guide;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getPlaceMax(): ?int
    {
        return $this->placeMax;
    }

    public function setPlaceMax(int $placeMax): static
    {
        $this->placeMax = $placeMax;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isAnnule(): ?bool
    {
        return $this->isAnnule;
    }

    public function setIsAnnule(bool $isAnnule): static
    {
        $this->isAnnule = $isAnnule;
        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(string $pays): static
    {
        $this->pays = $pays;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function isInterested(): bool
    {
        return $this->isInterested;
    }

    public function setIsInterested(bool $isInterested): self
    {
        $this->isInterested = $isInterested;
        return $this;
    }

    public function isLiked(): bool
    {
        return $this->isLiked;
    }

    public function setIsLiked(bool $isLiked): self
    {
        $this->isLiked = $isLiked;
        return $this;
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount): self
    {
        $this->likesCount = $likesCount;
        return $this;
    }

    public function getIsFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): self
    {
        $this->isFavorite = $isFavorite;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public static function getStatusChoices(): array
    {
        return [
            'Actif' => self::STATUS_ACTIF,
            'Terminé' => self::STATUS_TERMINE,
            'Annulé' => self::STATUS_ANNULE,
        ];
    }

    public static function getPaysChoices(): array
    {
        return [
            'France' => 'France',
            'Espagne' => 'Espagne',
            'Italie' => 'Italie',
            'Allemagne' => 'Allemagne',
            'Royaume-Uni' => 'Royaume-Uni',
            'Portugal' => 'Portugal',
            'Grèce' => 'Grèce',
            'Suisse' => 'Suisse',
            'Belgique' => 'Belgique',
            'Pays-Bas' => 'Pays-Bas',
            'Tunisie' => 'Tunisie',
            'Maroc' => 'Maroc',
            'Egypte' => 'Egypte',
            'Bali' => 'Bali',
            'Brésil' => 'Brésil'
        ];
    }

    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setEvenement($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getEvenement() === $this) {
                $commentaire->setEvenement(null);
            }
        }
        return $this;
    }
}
