<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "A planning must be assigned to a doctor.")]
    private ?User $doctor = null;

    #[ORM\Column(type: 'date', nullable: false)] // Ensure database enforces non-null
    #[Assert\NotNull(message: "Start date is required.")] // Prevent null submission
    #[Assert\Type("\DateTimeInterface", message: "Invalid date format.")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotNull(message: "End date is required.")]
    #[Assert\Type("\DateTimeInterface", message: "Invalid date format.")]
    #[Assert\GreaterThanOrEqual(propertyPath: "startDate", message: "End date must be after or equal to start date.")]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'time', nullable: true)]  // Champ nullable
    #[Assert\NotNull(message: "Daily end time is required.")]
    #[Assert\Type("\DateTimeInterface", message: "Invalid time format.")]
    private ?\DateTimeInterface $dailyStartTime = null;  // Valeur par défaut null

    #[ORM\Column(type: 'time', nullable: false)]
    #[Assert\NotNull(message: "Daily end time is required.")]
    #[Assert\Type("\DateTimeInterface", message: "Invalid time format.")]
    #[Assert\GreaterThan(propertyPath: "dailyStartTime", message: "Daily end time must be after start time.")]
    private ?\DateTimeInterface $dailyEndTime = null;


    #[ORM\OneToMany(mappedBy: 'planning', targetEntity: Appointment::class, cascade: ['persist', 'remove'])]
    private Collection $appointments;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
    }

    // Getters and setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDoctor(): ?User
    {
        return $this->doctor;
    }

    public function setDoctor(?User $doctor): self
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getDailyStartTime(): ?\DateTimeInterface
    {
        return $this->dailyStartTime;
    }

    public function setDailyStartTime(?\DateTimeInterface $dailyStartTime): self
    {
        $this->dailyStartTime = $dailyStartTime;
        return $this;
    }

    public function getDailyEndTime(): ?\DateTimeInterface
    {
        return $this->dailyEndTime;
    }

    public function setDailyEndTime(?\DateTimeInterface $dailyEndTime): self
    {
        $this->dailyEndTime = $dailyEndTime;
        return $this;
    }

    /**
     * @return Collection|Appointment[]
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments[] = $appointment;
            $appointment->setPlanning($this);
        }
        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            if ($appointment->getPlanning() === $this) {
                $appointment->setPlanning(null);
            }
        }
        return $this;
    }
}
