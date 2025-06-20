<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Post implements \ArrayAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Title cannot be empty")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Title must be at least {{ limit }} characters long",
        maxMessage: "Title cannot be longer than {{ limit }} characters"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Content cannot be empty")]
    #[Assert\Length(
        min: 10,
        minMessage: "Content must be at least {{ limit }} characters long"
    )]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_publication = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $nbre_vues = 0;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $statut = "pending";

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Category cannot be empty")]
    #[Assert\Choice(choices: [
        'Anxiété & Stress',
        'Développement Personnel',
        'Dépression & Bien-être Émotionnel',
        'Troubles Psychologiques',
        'Techniques & Thérapies'
    ], message: "Choose a valid category")]
    private ?string $categorie = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $nbre_comments = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image_post = null;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Commentaire::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->date_publication = new \DateTime();
    }

    // ✅ ID
    public function getId(): ?int
    {
        return $this->id;
    }

    // ✅ Titre
    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    // ✅ Contenu
    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    // ✅ Date Publication
    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    public function setDatePublication(?\DateTimeInterface $date_publication): static
    {
        $this->date_publication = $date_publication;
        return $this;
    }

    // ✅ Nombre de Vues
    public function getNbreVues(): ?int
    {
        return $this->nbre_vues;
    }

    public function setNbreVues(?int $nbre_vues): static
    {
        $this->nbre_vues = $nbre_vues;
        return $this;
    }

    // ✅ Statut
    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    // ✅ Catégorie
    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    // ✅ Nombre de Commentaires
    public function getNbreComments(): ?int
    {
        return $this->nbre_comments;
    }

    public function setNbreComments(?int $nbre_comments): static
    {
        $this->nbre_comments = $nbre_comments;
        return $this;
    }

    // ✅ Image Post
    public function getImagePost(): ?string
    {
        return $this->image_post;
    }

    public function setImagePost(?string $image_post): static
    {
        $this->image_post = $image_post;
        return $this;
    }

    // ✅ Comments
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Commentaire $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
        }
        return $this;
    }

    public function removeComment(Commentaire $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateCommentCount(): void
    {
        $this->nbre_comments = $this->comments->count();
    }

    // ✅ Relationship with User
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    // ✅ ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->{$offset});
    }
}
