<?php

namespace App\Security\Voter;

use App\Entity\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RouteVoter extends Voter
{
    const ACCESS_ROUTE = 'ACCESS_ROUTE';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function supports(string $attribute, $subject): bool
    {
        // This voter votes on 'ACCESS_ROUTE' attribute for Route entities or route names
        if ($attribute !== self::ACCESS_ROUTE) {
            return false;
        }

        return $subject instanceof Route || is_string($subject);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // If the user is not an instance of your User class, deny access
        if (!$user instanceof User) {
            return false;
        }

        // dd($subject);

        // Convert subject to Route object if it's a string (route name)
        if (is_string($subject)) {
            $route = $this->entityManager->getRepository(Route::class)->findOneBy(['name' => $subject]);
        } else {
            $route = $subject;
        }

        if (!$route instanceof Route) {
            return false; // If no route found or invalid subject type
        }

        // dd($route);

        // Retrieve the user's roles
        $userRoles = $user->getRole();
        $this->entityManager->initializeObject($userRoles);

        $currentRoles = [$userRoles->getRoleKey()];

        // dd($currentRoles);


        // Check if any of the user's roles match any role that has access to the route
        foreach ($route->getRoles() as $allowedRole) {
            // if (in_array($allowedRole->getRoleKey(), $currentRoles)) {
            //     return true;
            // }
            if ($allowedRole->getRoleKey() === $user->getRole()->getRoleKey()) {
                return true;
            }
        }

        return false;
    }
}
