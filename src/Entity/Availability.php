<?php

namespace App\Entity;

use App\Repository\AvailabilityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Filter\RecursiveDateFilter;
use App\Filter\RecursiveSearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
#[ApiFilter(RecursiveSearchFilter::class)]
#[ApiFilter(RecursiveDateFilter::class)]
#[ApiResource(
    paginationClientItemsPerPage: true,
    normalizationContext: ['groups' => ['availability:read']],
    operations: [
        new GetCollection(
            security: "is_granted('ACCESS_ROUTE', 'SHOW_ALL_AVAILABILITIES')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Get(
            security: "is_granted('ACCESS_ROUTE', 'SHOW_AVAILABILITY')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Post(
            security: "is_granted('ACCESS_ROUTE', 'ADD_AVAILABILITY')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Put(
            security: "is_granted('ACCESS_ROUTE', 'UPDATE_AVAILABILITY')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Delete(
            security: "is_granted('ACCESS_ROUTE', 'DELETE_AVAILABILITY')",
            securityMessage: 'You are not authorized to access this resource.',
        )
    ]
)]
class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'availabilities')]
    private ?User $doctor = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $endTime = null;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(mappedBy: 'availability', targetEntity: Appointment::class)]
    private Collection $appointments;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDoctor(): ?User
    {
        return $this->doctor;
    }

    public function setDoctor(?User $doctor): static
    {
        $this->doctor = $doctor;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setAvailability($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getAvailability() === $this) {
                $appointment->setAvailability(null);
            }
        }

        return $this;
    }
}
