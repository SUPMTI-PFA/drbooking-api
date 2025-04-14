<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\FcmTokenRepository;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use App\Filter\RecursiveDateFilter;
use App\Filter\RecursiveSearchFilter;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: FcmTokenRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable]
#[ApiResource(
    paginationClientItemsPerPage: true,
    normalizationContext: ['groups' => ['fcmToken:all']],
    operations: [
        new GetCollection(
            // security: "is_granted('ROLE_USER')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Get(
            // security: "is_granted('ROLE_USER')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Post(
            // security: "is_granted('ROLE_USER')",
            securityMessage: 'You are not authorized to access this resource.',
            // deserialize: false,
            // controller: FcmTokenController::class
        ),
        new Delete(
            // security: "is_granted('ROLE_USER')",
            securityMessage: 'You are not authorized to access this resource.',
        ),
        new Put(
            // security: "is_granted('ROLE_USER')",
            securityMessage: 'You are not authorized to access this resource.',
        )
    ]
)]
#[ApiFilter(RecursiveSearchFilter::class)]
#[ApiFilter(RecursiveDateFilter::class)]
class FcmToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["fcmToken:all"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["fcmToken:all"])]
    private ?string $token = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(["fcmToken:all"])]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(["fcmToken:all"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["fcmToken:all"])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'deletedAt', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["fcmToken:all"])]
    private  $deletedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }


    #[ORM\PrePersist]
    public function setUpdatedAtDefaultValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
