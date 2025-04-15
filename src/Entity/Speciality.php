<?php

namespace App\Entity;

use App\Repository\SpecialityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpecialityRepository::class)]
class Speciality
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
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
