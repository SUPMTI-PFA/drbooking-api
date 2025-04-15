<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class MeProvider implements ProviderInterface
{
    public function __construct(private Security $security)
    {
    }

    private function getDataOfUser(User $user, string $accountType): object
    {
        $model = match ($accountType) {
            // 'DRIVER' => $user,
            // 'DRIVER' => $user->getDriver(),
            // 'PASSENGER' => $user->getPassenger(),
            default => $user,
        };

        return $model;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        /** @var User $user */
        return $this->getDataOfUser($user, $user->getAccountType()->value);
    }
}
