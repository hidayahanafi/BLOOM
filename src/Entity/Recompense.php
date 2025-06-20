<?php

namespace App\Entity;

use App\Repository\RecompenseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecompenseRepository::class)]
class Recompense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom est trop long."
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "La description est trop longue."
    )]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: "Le coût doit être un nombre positif ou zéro.")]
    private ?int $cout = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'état est obligatoire.")]
    #[Assert\Choice(
        choices: ["disponible", "verrouillé", "réclamé"],
        message: "L'état doit être 'disponible', 'verrouillé' ou 'réclamé'."
    )]
    private ?string $etat = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le chemin de l'image est trop long."
    )]
    private ?string $img = null;

    #[ORM\OneToOne(targetEntity: Objectif::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'id_objectif', referencedColumnName: 'id', nullable: true)]
    private ?Objectif $objectif = null;

    // Relation avec User, nullable
    #[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(nullable: true)]
private ?User $user = null;

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

    public function getCout(): ?int
    {
        return $this->cout;
    }

    public function setCout(?int $cout): static
    {
        $this->cout = $cout;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

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

    public function getObjectif(): ?Objectif
    {
        return $this->objectif;
    }

    public function setObjectif(?Objectif $objectif): static
    {
        $this->objectif = $objectif;

        return $this;
    }

    // Getters et Setters pour la relation avec User
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function updateEtat(): void
    {
        // Vérifie si l'objectif est associé à la récompense
        if ($this->objectif !== null) {
            // Si l'objectif a un statut différent de "Terminé"
            if ($this->objectif->getStatut() !== 'Terminé') {
                $this->etat = 'verrouillé';  // L'état de la récompense devient verrouillé
            } else {
                $this->etat = 'disponible';  // Si l'objectif est terminé, l'état devient disponible
            }
        }
    }
}
