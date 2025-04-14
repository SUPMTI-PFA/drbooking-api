<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserProvider implements UserProviderInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => $identifier])
            ?? $userRepository->findOneBy(['username' => $identifier]);

        if (!$user) {
            throw new UnsupportedUserException('No user found for identifier ' . $identifier);
        }

        return $user;
        // $serializedUser = $this->serializer->serialize($user, 'json', ['groups' => ['user:read']]);
        // return $serializedUser;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
