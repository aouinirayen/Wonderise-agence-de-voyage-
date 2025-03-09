<?php

namespace App\Entity;

use App\Repository\MonumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: MonumentRepository::class)]
class Monument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['monument:read', 'country:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['monument:read', 'country:read'])]
    #[Assert\NotBlank(message: 'Name cannot be empty')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters',
        maxMessage: 'Name cannot exceed {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9\s\-]+$/',
        message: 'Name can only contain letters, numbers, spaces and hyphens'
    )]
    private ?string $name = null;

    /**
     * @var File|null
     */
    #[Assert\File(
        maxSize: '1024k',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
        mimeTypesMessage: 'Please upload a valid image file'
    )]
    private $imageFile;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['monument:read', 'country:read'])]
    private ?string $img = null;

    #[ORM\Column(length: 1000, nullable: true)]
    #[Groups(['monument:read', 'country:read'])]
    #[Assert\NotBlank(message: 'Description cannot be empty')]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Description cannot exceed {{ limit }} characters'
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'monuments')]
    #[Groups(['monument:read'])]
    #[Assert\NotNull(message: 'Please select a country')]
    private ?Country $country = null;

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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;
        return $this;
    }
}
