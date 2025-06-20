<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Comment content cannot be empty")]
    #[Assert\Length(
        min: 5,
        max: 5000,
        minMessage: "Comment must be at least {{ limit }} characters",
        maxMessage: "Comment cannot be longer than {{ limit }} characters"
    )]
    private ?string $comm_content = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $comm_date = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(name: 'parent_id', onDelete: 'CASCADE', nullable: true)]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $replies;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $evaluation_score = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => false])]
    private bool $isBlocked = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null; // ✅ Now linked to User entity

    public function __construct()
    {
        $this->replies = new ArrayCollection();
        $this->comm_date = new \DateTime();
    }

    // ✅ Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommContent(): ?string
    {
        return $this->comm_content;
    }

    public function getCommDate(): ?\DateTimeInterface
    {
        return $this->comm_date;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getEvaluationScore(): ?int
    {
        return $this->evaluation_score;
    }

    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    // ✅ Setters
    public function setCommContent(string $comm_content): static
    {
        $this->comm_content = $comm_content;
        return $this;
    }

    public function setCommDate(\DateTimeInterface $comm_date): static
    {
        $this->comm_date = $comm_date;
        return $this;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;
        return $this;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    public function setEvaluationScore(?int $evaluation_score): static
    {
        $this->evaluation_score = $evaluation_score;
        return $this;
    }

    public function setIsBlocked(bool $isBlocked): static
    {
        $this->isBlocked = $isBlocked;
        return $this;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    // ✅ Collection methods
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(self $reply): static
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setParent($this);
        }
        return $this;
    }

    public function removeReply(self $reply): static
    {
        if ($this->replies->removeElement($reply) && $reply->getParent() === $this) {
            $reply->setParent(null);
        }
        return $this;
    }

    public function isReply(): bool
    {
        return $this->parent !== null;
    }
}
