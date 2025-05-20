<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Delete;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\State\MeProvider;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\UserController;
use App\Enum\AccountType;
use App\Filter\RecursiveDateFilter;
use App\Filter\RecursiveSearchFilter;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[Vich\Uploadable]
#[UniqueEntity(fields: ['email', 'username'], message: 'field duplicated')]
#[ApiFilter(RecursiveSearchFilter::class)]
#[ApiFilter(RecursiveDateFilter::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    operations: [
        new GetCollection(
            // security: "is_granted('ACCESS_ROUTE', 'SHOW_ALL_USERS')",
            // securityMessage: 'You are not authorized to access this resource.',
        ),
        new Get(
            // security: "is_granted('ACCESS_ROUTE', 'SHOW_USER')",
            // securityMessage: 'You are not authorized to access this resource.',
        ),
        new Post(
            deserialize: false,
            controller: UserController::class,
            // security: "is_granted('ACCESS_ROUTE', 'ADD_USER')",
            // securityMessage: 'You are not authorized to access this resource.',
        ),
        new Post(
            deserialize: false,
            controller: UserController::class . '::forgottenPassword',
            uriTemplate: "/users/password/forgot",
            // security: "is_granted('ACCESS_ROUTE', 'ADD_PROMO_CODE')",
            // securityMessage: 'You are not authorized to access this resource.',
        ),
        new Put(
            deserialize: false,
            controller: UserController::class . '::__invokePUT',
            security: "is_granted('ACCESS_ROUTE', 'UPDATE_USER')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Delete(
            security: "is_granted('ACCESS_ROUTE', 'DELETE_USER')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Get(
            uriTemplate: "/me",
            provider: MeProvider::class
        )
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user:read", "user:write"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?string $slug = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?string $email = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?string $fullName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?string $telephone = null;

    #[ORM\Column]
    #[Groups(["user:read", "user:write"])]
    private ?bool $newsletter = null;

    #[ORM\Column]
    #[Groups(["user:read", "user:write"])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?string $photo = null;

    #[Vich\UploadableField(mapping: "user_images", fileNameProperty: "photo")]
    private ?File $file = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(["user:read", "user:write"])]
    private ?Role $role = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?string $fcmToken = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(["user:read", "user:write", "user:write"])]
    private ?DoctorProfile $doctorProfile = null;

    /**
     * @var Collection<int, Availability>
     */
    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Availability::class)]
    #[Groups(["user:read", "user:write"])]
    private Collection $availabilities;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: Appointment::class)]
    #[Groups(["user:read", "user:write"])]
    private Collection $appointments;

    #[ORM\Column(length: 255, enumType: AccountType::class)]
    #[Groups(["user:read", "user:write"])]
    private ?AccountType $accountType = null;

    #[ORM\Column(length: 255)]
    #[Groups(["user:read", "user:write"])]
    private ?string $gender = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->availabilities = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->newsletter = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    // #[Groups(["user:read", "user:write"])]
    // public function getFullName(): string
    // {
    //     return trim($this->firstName . ' ' . $this->lastName);
    // }

    public function getPhoto(): ?string
    {
        return  $this->photo ? "/uploads/user_images/" . $this->photo : $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }


    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file = null): void
    {
        $this->file = $file;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function isNewsletter(): ?bool
    {
        return $this->newsletter;
    }

    public function setNewsletter(bool $newsletter): static
    {
        $this->newsletter = $newsletter;

        return $this;
    }

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(?string $fcmToken): static
    {
        $this->fcmToken = $fcmToken;

        return $this;
    }

    public function getDoctorProfile(): ?DoctorProfile
    {
        return $this->doctorProfile;
    }

    public function setDoctorProfile(?DoctorProfile $doctorProfile): static
    {
        // unset the owning side of the relation if necessary
        if ($doctorProfile === null && $this->doctorProfile !== null) {
            $this->doctorProfile->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($doctorProfile !== null && $doctorProfile->getUser() !== $this) {
            $doctorProfile->setUser($this);
        }

        $this->doctorProfile = $doctorProfile;

        return $this;
    }

    /**
     * @return Collection<int, Availability>
     */
    public function getAvailabilities(): Collection
    {
        return $this->availabilities;
    }

    public function addAvailability(Availability $availability): static
    {
        if (!$this->availabilities->contains($availability)) {
            $this->availabilities->add($availability);
            $availability->setDoctor($this);
        }

        return $this;
    }

    public function removeAvailability(Availability $availability): static
    {
        if ($this->availabilities->removeElement($availability)) {
            // set the owning side to null (unless already changed)
            if ($availability->getDoctor() === $this) {
                $availability->setDoctor(null);
            }
        }

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
            $appointment->setPatient($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getPatient() === $this) {
                $appointment->setPatient(null);
            }
        }

        return $this;
    }

    public function getAccountType(): ?AccountType
    {
        return $this->accountType;
    }

    public function setAccountType(AccountType $accountType): static
    {
        $this->accountType = $accountType;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }
}
