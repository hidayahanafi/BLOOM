<?php

namespace App\Entity;

use App\Repository\ObjectifRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ObjectifRepository::class)]
class Objectif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "La description est trop longue."
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type(
        "\DateTimeInterface",
        message: "La date de début doit être une date valide."
    )]
    #[Assert\GreaterThanOrEqual(
        "today",
        message: "La date de début ne peut pas être dans le passé."
    )]
    private ?\DateTimeInterface $datedebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type(
        "\DateTimeInterface",
        message: "La date de fin doit être une date valide."
    )]
    #[Assert\GreaterThanOrEqual(
        "today",
        message: "La date de fin ne peut pas être dans le passé."
    )]
    #[Assert\Expression(
        "this.getDatedebut() === null || this.getDatefin() > this.getDatedebut()",
        message: "La date de fin doit être après la date de début."
    )]
    private ?\DateTimeInterface $datefin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(
        choices: ["En cours", "Terminé", "Annulé", "Non commencé"],
        message: "Le statut doit être 'En cours', 'Terminé', 'Non commencé'."
    )]
    private ?string $statut = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom est trop long."
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "La récompense est trop longue."
    )]
    private ?string $recompense = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: "Le nombre de points doit être un entier positif ou zéro.")]
    private ?int $NbPts = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le chemin de l'image est trop long."
    )]
    private ?string $img = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDatedebut(): ?\DateTimeInterface
    {
        return $this->datedebut;
    }

    public function setDatedebut(?\DateTimeInterface $datedebut): static
    {
        $this->datedebut = $datedebut;
        return $this;
    }

    public function getDatefin(): ?\DateTimeInterface
    {
        return $this->datefin;
    }

    public function setDatefin(?\DateTimeInterface $datefin): static
    {
        $this->datefin = $datefin;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
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

    public function getRecompense(): ?string
    {
        return $this->recompense;
    }

    public function setRecompense(?string $recompense): static
    {
        $this->recompense = $recompense;
        return $this;
    }

    public function getNbPts(): ?int
    {
        return $this->NbPts;
    }

    public function setNbPts(?int $NbPts): static
    {
        $this->NbPts = $NbPts;
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
}
