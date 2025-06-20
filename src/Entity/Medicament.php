<?php

namespace App\Entity;

use App\Repository\MedicamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MedicamentRepository::class)]
class Medicament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du médicament est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.',
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La description du médicament est obligatoire.')]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.',
    )]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le dosage est obligatoire.')]
    #[Assert\Positive(message: 'Le dosage doit être un nombre positif.')]
    private ?int $dosage = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La catégorie du médicament est obligatoire.')]
    #[Assert\Choice(
        choices: ['antidouleur', 'antibiotique', 'antihistaminique', 'autre'],
        message: 'La catégorie doit être l\'une des suivantes : antidouleur, antibiotique, antihistaminique, autre.'
    )]
    private ?string $categorie = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La quantité est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La quantité doit être un nombre positif ou zéro.')]
    private ?int $quantite = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le prix est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être un nombre positif.')]
    private ?float $prix = null;

    #[ORM\Column(type: Types::BLOB)]
    #[Assert\NotBlank(message: 'L\'image est obligatoire.')]
    #[Assert\File(
        maxSize: '2M', // Limite la taille du fichier à 2 Mo
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'], // Types MIME autorisés
        mimeTypesMessage: 'Veuillez uploader une image valide (JPEG, PNG ou GIF).'
    )]
    private $imageurl = null;

    /**
     * @var Collection<int, Pharmacie>
     */
    #[ORM\ManyToMany(targetEntity: Pharmacie::class, inversedBy: 'medicaments')]
    private Collection $id_pharmacie;

    /**
     * @var Collection<int, Panier>
     */
    #[ORM\ManyToMany(targetEntity: Panier::class, mappedBy: 'medicaments')]
    private Collection $paniers;


    public function __construct()
    {
        $this->id_pharmacie = new ArrayCollection();
        $this->paniers = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDosage(): ?int
    {
        return $this->dosage;
    }

    public function setDosage(int $dosage): static
    {
        $this->dosage = $dosage;

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

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

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

    public function getImageurl()
    {
        return $this->imageurl;
    }

    public function setImageurl($imageurl): static
    {
        $this->imageurl = $imageurl;

        return $this;
    }

    /**
     * @return Collection<int, Pharmacie>
     */
    public function getIdPharmacie(): Collection
    {
        return $this->id_pharmacie;
    }

    public function addIdPharmacie(Pharmacie $idPharmacie): static
    {
        if (!$this->id_pharmacie->contains($idPharmacie)) {
            $this->id_pharmacie->add($idPharmacie);
        }

        return $this;
    }

    public function removeIdPharmacie(Pharmacie $idPharmacie): static
    {
        $this->id_pharmacie->removeElement($idPharmacie);

        return $this;
    }

    /**
     * @return Collection<int, Panier>
     */
    public function getPaniers(): Collection
    {
        return $this->paniers;
    }

    public function addPanier(Panier $panier): static
    {
        if (!$this->paniers->contains($panier)) {
            $this->paniers->add($panier);
            $panier->addMedicament($this);
        }

        return $this;
    }

    public function removePanier(Panier $panier): static
    {
        if ($this->paniers->removeElement($panier)) {
            $panier->removeMedicament($this);
        }

        return $this;
    }



}