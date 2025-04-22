<?php

namespace App\Entity;

use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
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

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ApiFilter(RecursiveSearchFilter::class)]
#[ApiFilter(RecursiveDateFilter::class)]
#[ApiResource(
    paginationClientItemsPerPage: true,
    normalizationContext: ['groups' => ['appointment:read']],
    operations: [
        new GetCollection(
            security: "is_granted('ACCESS_ROUTE', 'SHOW_ALL_APPOINTMENTS')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Get(
            security: "is_granted('ACCESS_ROUTE', 'SHOW_APPOINTMENT')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Post(
            security: "is_granted('ACCESS_ROUTE', 'ADD_APPOINTMENT')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Put(
            security: "is_granted('ACCESS_ROUTE', 'UPDATE_APPOINTMENT')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Delete(
            security: "is_granted('ACCESS_ROUTE', 'DELETE_APPOINTMENT')",
            securityMessage: 'You are not authorized to access this resource.',
        )
    ]
)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    private ?User $patient = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    private ?User $doctor = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    private ?Availability $availability = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $reason = null;

    #[ORM\Column(enumType: AppointmentStatus::class)]
    private AppointmentStatus $status = AppointmentStatus::PENDING;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): static
    {
        $this->patient = $patient;

        return $this;
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

    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function setAvailability(?Availability $availability): static
    {
        $this->availability = $availability;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getStatus(): ?AppointmentStatus
    {
        return $this->status;
    }

    public function setStatus(AppointmentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
