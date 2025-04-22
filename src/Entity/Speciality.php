<?php

namespace App\Entity;

use App\Repository\SpecialityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

#[ORM\Entity(repositoryClass: SpecialityRepository::class)]
#[ApiFilter(RecursiveSearchFilter::class)]
#[ApiFilter(RecursiveDateFilter::class)]
#[ApiResource(
    paginationClientItemsPerPage: true,
    normalizationContext: ['groups' => ['speciality:read']],
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
class Speciality
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user:read", "speciality:read", "doctorProfile:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["user:read", "speciality:read", "doctorProfile:read"])]
    private ?string $name = null;

    /**
     * @var Collection<int, DoctorProfile>
     */
    #[ORM\OneToMany(mappedBy: 'speciality', targetEntity: DoctorProfile::class)]
    private Collection $doctorProfiles;

    public function __construct()
    {
        $this->doctorProfiles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, DoctorProfile>
     */
    public function getDoctorProfiles(): Collection
    {
        return $this->doctorProfiles;
    }

    public function addDoctorProfile(DoctorProfile $doctorProfile): static
    {
        if (!$this->doctorProfiles->contains($doctorProfile)) {
            $this->doctorProfiles->add($doctorProfile);
            $doctorProfile->setSpeciality($this);
        }

        return $this;
    }

    public function removeDoctorProfile(DoctorProfile $doctorProfile): static
    {
        if ($this->doctorProfiles->removeElement($doctorProfile)) {
            // set the owning side to null (unless already changed)
            if ($doctorProfile->getSpeciality() === $this) {
                $doctorProfile->setSpeciality(null);
            }
        }

        return $this;
    }
}
