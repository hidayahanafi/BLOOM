<?php

namespace App\Entity;

use App\Repository\PanierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paniers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $id_user = null;
    #[ORM\Column]
    private ?int $quantite = 1;
    
    public function getQuantite(): ?int
    {
        return $this->quantite;
    }
    
    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
    
        return $this;
    }
    
    /**
     * @var Collection<int, Medicament>
     */
    #[ORM\ManyToMany(targetEntity: Medicament::class, inversedBy: 'paniers')]
    private Collection $medicaments;
    #[ORM\Column(type: 'json')]
    private array $medicamentQuantities = [];
    
    public function getMedicamentQuantities(): array
    {
        return $this->medicamentQuantities;
    }
    
    public function setMedicamentQuantities(array $medicamentQuantities): static
    {
        $this->medicamentQuantities = $medicamentQuantities;
        return $this;
    }
    
    #[ORM\Column]
    private ?\DateTimeImmutable $dateAjout = null;

    public function __construct()
    {
        $this->medicaments = new ArrayCollection();
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

    public function getIdUser(): ?user
    {
        return $this->id_user;
    }

    public function setIdUser(?User $id_user): static
    {
        $this->id_user = $id_user;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity=Pharmacie::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $pharmacie;

    // Méthodes getter et setter pour pharmacie
    public function getPharmacie(): ?Pharmacie
    {
        return $this->pharmacie;
    }

    public function setPharmacie(?Pharmacie $pharmacie): self
    {
        $this->pharmacie = $pharmacie;

        return $this;
    }

    /**
     * @return Collection<int, Medicament>
     */
    public function getMedicaments(): Collection
    {
        return $this->medicaments;
    }

    public function addMedicament(Medicament $medicament): static
    {
        if (!$this->medicaments->contains($medicament)) {
            $this->medicaments->add($medicament);
        }

        return $this;
    }
    public function setMedicament(Medicament $medicament): self
    {
        $this->medicaments[] = $medicament;
        return $this;
    }
    public function removeMedicament(Medicament $medicament): static
    {
        $this->medicaments->removeElement($medicament);

        return $this;
    }

    public function getDateAjout(): ?\DateTimeImmutable
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTimeImmutable $dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }
}
