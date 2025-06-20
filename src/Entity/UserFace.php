<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\UserFaceRepository")]
#[ORM\Table(name: "user_faces")]
class UserFace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "integer")]
    private $userId;

    #[ORM\Column(type: "text", nullable: true)]
    private $faceEncoding;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getFaceEncoding(): ?string
    {
        return $this->faceEncoding;
    }

    public function setFaceEncoding(string $faceEncoding): self
    {
        $this->faceEncoding = $faceEncoding;
        return $this;
    }
}
