<?php

namespace App\Entity;

use App\Repository\DoctorEducationRepository;
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

#[ORM\Entity(repositoryClass: DoctorEducationRepository::class)]
#[ApiFilter(RecursiveSearchFilter::class)]
#[ApiFilter(RecursiveDateFilter::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['doctorEducation:read']],
    // denormalizationContext: ['groups' => ['user:write']],
    operations: [
        new GetCollection(
            // security: "is_granted('ACCESS_ROUTE', 'SHOW_ALL_DOCTOR_PROFILES')",
            // securityMessage: 'You are not authorized to access this resource.',
        ),
        new Get(
            // security: "is_granted('ACCESS_ROUTE', 'SHOW_DOCTOR_PROFILE')",
            // securityMessage: 'You are not authorized to access this resource.',
        ),
        new Post(
            // security: "is_granted('ACCESS_ROUTE', 'ADD_DOCTOR_PROFILE')",
            // securityMessage: 'You are not authorized to access this resource.',
        ),
        new Put(
            // security: "is_granted('ACCESS_ROUTE', 'UPDATE_DOCTOR_PROFILE')",
            // securityMessage: 'You are not authorized to access this resource.',
        ),
        new Delete(
            // security: "is_granted('ACCESS_ROUTE', 'DELETE_DOCTOR_PROFILE')",
            // securityMessage: 'You are not authorized to access this resource.',
        )
    ]
)]
class DoctorEducation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user:read", "doctorProfile:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["user:read", "doctorProfile:read"])]
    private ?string $degree = null;

    #[ORM\Column(length: 255)]
    #[Groups(["user:read", "doctorProfile:read"])]
    private ?string $institution = null;

    #[ORM\Column(length: 4)]
    #[Groups(["user:read", "doctorProfile:read"])]
    private ?string $year = null;

    #[ORM\ManyToOne(inversedBy: 'doctorEducation')]
    private ?DoctorProfile $doctorProfile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDegree()
    {
        return $this->degree;
    }

    public function setDegree($degree)
    {
        $this->degree = $degree;

        return $this;
    }

    public function getInstitution()
    {
        return $this->institution;
    }

    public function setInstitution($institution)
    {
        $this->institution = $institution;

        return $this;
    }

    public function getYear()
    {
        return $this->year;
    }
   
    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    public function getDoctorProfile(): ?DoctorProfile
    {
        return $this->doctorProfile;
    }

    public function setDoctorProfile(?DoctorProfile $doctorProfile): static
    {
        $this->doctorProfile = $doctorProfile;

        return $this;
    }
}
