<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $coverPicture = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Please enter an email.')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Name must be at least 2 characters long.')]
    private ?string $name = null;

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\Positive(message: 'Age must be a positive number.')]
    #[Assert\LessThan(value: 100, message: 'Age must be realistic.')]
    private ?int $age = null;

    #[ORM\Column(type: "string", length: 15, nullable: true)]
    #[Assert\Regex(pattern: '/^\+?[0-9]{8,15}$/', message: 'Invalid phone number format.')]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Country name is too long.')]
    private ?string $country = null;


    // Fields for patients

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $relationshipStatus = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    #[Assert\Length(max: 30, maxMessage: 'Religion cannot be longer than 30 characters.')]
    private ?string $religion = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $religionImportance = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $therapyExperience = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $therapyReasons = [];

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $therapyType = null;


    /**
     * Temporary field for plain password during registration.
     */
    #[Assert\NotBlank(message: 'Password cannot be blank.', groups: ['Registration'])]
    #[Assert\Length(min: 6, minMessage: 'Password must be at least 6 characters long.')]
    private ?string $plainPassword = null;

    // Email verification fields

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: "boolean")]
    private ?bool $isVerified = false;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $phoneVerificationCode;

    #[ORM\Column(type: "boolean")]
    private $isPhoneVerified = false;


    // Doctor-specific fields

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $experience = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $doctorType = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $specializations = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $languagesSpoken = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $therapeuticApproaches = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $appointmentMethods = [];

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $diploma = null;

    // Forgot password fields

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $passwordResetRequestedAt = null;


    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $githubId = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $linkedinId = null;


    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $faceEmbeddings = null;


    #[ORM\Column(type: 'boolean')]
    private bool $isBlocked = false;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $faceEmbedding;


    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'users')]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    //Hidaya
    #[ORM\OneToMany(mappedBy: "user", targetEntity: Appointment::class, cascade: ["remove"])]
    private Collection $appointments;


    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getCoverPicture(): ?string
    {
        return $this->coverPicture;
    }

    public function setCoverPicture(?string $coverPicture): self
    {
        $this->coverPicture = $coverPicture;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setDefaultProfilePicture(): void
    {
        if ($this->profilePicture === null) {
            $this->profilePicture = 'assets/profilePics/default.png';
        }
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;
        return $this;
    }


    public function getPhoneVerificationCode(): ?string
    {
        return $this->phoneVerificationCode;
    }

    public function setPhoneVerificationCode(?string $phoneVerificationCode): self
    {
        $this->phoneVerificationCode = $phoneVerificationCode;
        return $this;
    }

    public function getIsPhoneVerified(): bool
    {
        return $this->isPhoneVerified;
    }

    public function setIsPhoneVerified(bool $isPhoneVerified): self
    {
        $this->isPhoneVerified = $isPhoneVerified;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getRelationshipStatus(): ?string
    {
        return $this->relationshipStatus;
    }

    public function setRelationshipStatus(?string $relationshipStatus): self
    {
        $this->relationshipStatus = $relationshipStatus;
        return $this;
    }

    public function getReligion(): ?string
    {
        return $this->religion;
    }

    public function setReligion(?string $religion): self
    {
        $this->religion = $religion;
        return $this;
    }

    public function getReligionImportance(): ?string
    {
        return $this->religionImportance;
    }

    public function setReligionImportance(?string $religionImportance): self
    {
        $this->religionImportance = $religionImportance;
        return $this;
    }

    public function getTherapyExperience(): ?bool
    {
        return $this->therapyExperience;
    }

    public function setTherapyExperience(?bool $therapyExperience): self
    {
        $this->therapyExperience = $therapyExperience;
        return $this;
    }

    public function getTherapyReasons(): ?array
    {
        return $this->therapyReasons;
    }

    public function setTherapyReasons(?array $therapyReasons): self
    {
        $this->therapyReasons = $therapyReasons;
        return $this;
    }

    public function addTherapyReason(string $reason): self
    {
        if ($this->therapyReasons === null) {
            $this->therapyReasons = []; // Initialize as an empty array
        }

        if (!in_array($reason, $this->therapyReasons, true)) {
            $this->therapyReasons[] = $reason;
        }

        return $this;
    }


    public function removeTherapyReason(string $reason): self
    {
        $this->therapyReasons = array_filter($this->therapyReasons, fn($r) => $r !== $reason);
        return $this;
    }

    public function getTherapyType(): ?string
    {
        return $this->therapyType;
    }

    public function setTherapyType(?string $therapyType): self
    {
        $this->therapyType = $therapyType;
        return $this;
    }

    public function getSpecializations(): array
    {
        return $this->specializations ?? [];
    }

    public function setSpecializations(array $specializations): self
    {
        $this->specializations = $specializations;
        return $this;
    }

    public function getExperience(): ?int
    {
        return $this->experience;
    }

    public function setExperience(?int $experience): self
    {
        $this->experience = $experience;
        return $this;
    }

    public function getLanguagesSpoken(): array
    {
        return $this->languagesSpoken;
    }

    public function setLanguagesSpoken(array $languagesSpoken): self
    {
        $this->languagesSpoken = $languagesSpoken;
        return $this;
    }

    public function getTherapeuticApproaches(): array
    {
        return $this->therapeuticApproaches;
    }

    public function setTherapeuticApproaches(array $therapeuticApproaches): self
    {
        $this->therapeuticApproaches = $therapeuticApproaches;
        return $this;
    }

    public function getAppointmentMethods(): array
    {
        return $this->appointmentMethods;
    }

    public function setAppointmentMethods(array $appointmentMethods): self
    {
        $this->appointmentMethods = $appointmentMethods;
        return $this;
    }


    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }

    public function getIsVerified(): bool
    {
        error_log("User isVerified: " . $this->isVerified);
        return (bool) $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }



    // Forgot password getters and setters

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): self
    {
        $this->passwordResetToken = $passwordResetToken;
        return $this;
    }

    public function getPasswordResetRequestedAt(): ?\DateTimeInterface
    {
        return $this->passwordResetRequestedAt;
    }

    public function setPasswordResetRequestedAt(?\DateTimeInterface $passwordResetRequestedAt): self
    {
        $this->passwordResetRequestedAt = $passwordResetRequestedAt;
        return $this;
    }


    public function getDoctorType(): ?string
    {
        return $this->doctorType;
    }
    public function setDoctorType(?string $doctorType): self
    {
        $this->doctorType = $doctorType;
        return $this;
    }

    public function getDiploma(): ?string
    {
        return $this->diploma;
    }
    public function setDiploma(?string $diploma): self
    {
        $this->diploma = $diploma;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;
        return $this;
    }


    public function getGithubId(): ?string
    {
        return $this->githubId;
    }

    public function setGithubId(?string $githubId): self
    {
        $this->githubId = $githubId;
        return $this;
    }

    public function getLinkedinId(): ?string
    {
        return $this->linkedinId;
    }

    public function setLinkedinId(?string $linkedinId): self
    {
        $this->linkedinId = $linkedinId;
        return $this;
    }

    public function getFaceEmbeddings(): ?array
    {
        return $this->faceEmbeddings;
    }

    public function setFaceEmbeddings(?array $faceEmbeddings): self
    {
        $this->faceEmbeddings = $faceEmbeddings;
        return $this;
    }

    public function getIsBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): self
    {
        $this->isBlocked = $isBlocked;
        return $this;
    }


    public function getFaceEmbedding(): ?array
    {
        return $this->faceEmbedding;
    }

    public function setFaceEmbedding(array $faceEmbedding): self
    {
        $this->faceEmbedding = $faceEmbedding;
        return $this;
    }

    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setUser($this);
        }
        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            if ($appointment->getUser() === $this) {
                $appointment->setUser(null);
            }
        }
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this; // Return the object itself to allow method chaining
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeUser($this);
        }

        return $this;
    }


}
