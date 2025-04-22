<?php

namespace App\Entity;

use App\Repository\DoctorProfileRepository;
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

#[ORM\Entity(repositoryClass: DoctorProfileRepository::class)]
#[ApiFilter(RecursiveSearchFilter::class)]
#[ApiFilter(RecursiveDateFilter::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['doctorProfile:read']],
    // denormalizationContext: ['groups' => ['user:write']],
    operations: [
        new GetCollection(
            security: "is_granted('ACCESS_ROUTE', 'SHOW_ALL_DOCTOR_PROFILES')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Get(
            security: "is_granted('ACCESS_ROUTE', 'SHOW_DOCTOR_PROFILE')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Post(
            security: "is_granted('ACCESS_ROUTE', 'ADD_DOCTOR_PROFILE')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Put(
            security: "is_granted('ACCESS_ROUTE', 'UPDATE_DOCTOR_PROFILE')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Delete(
            security: "is_granted('ACCESS_ROUTE', 'DELETE_DOCTOR_PROFILE')",
            securityMessage: 'You are not authorized to access this resource.',
        )
    ]
)]
class DoctorProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user:read", "doctorProfile:read"])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'doctorProfile', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'doctorProfiles')]
    #[Groups(["user:read", "doctorProfile:read"])]
    private ?Speciality $speciality = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["user:read", "doctorProfile:read"])]
    private ?string $address = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["user:read", "doctorProfile:read"])]
    private ?string $bio = null;

    #[ORM\Column]
    #[Groups(["user:read", "doctorProfile:read"])]
    private ?bool $isActive = true;

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

    public function getSpeciality(): ?Speciality
    {
        return $this->speciality;
    }

    public function setSpeciality(?Speciality $speciality): static
    {
        $this->speciality = $speciality;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }
}
