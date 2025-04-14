<?php

namespace App\Controller;

use App\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserService $userService,
        private UserPasswordHasherInterface $passwordEncoder,
        private UserRepository $userRepository
    ) {}

    public function __invoke(Request $request): Response
    {
        $role = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'ROLE_USER']);
        $request->request->set('roles', ['ROLE_USER']);
        $request->request->set('role', "api/roles/{$role->getId()}");

        $this->userService->createUser($request);

        return new Response('User added successfully', Response::HTTP_CREATED);
    }

    #[IsGranted('ROLE_ADMIN', message: 'You are not authorized to access this resource.')]
    #[Route('api/admin/signup', name: 'new_admin', methods: ['POST'])]
    public function newAdmin(Request $request): Response
    {
        // $role = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'ROLE_USER']);
        // $request->request->set('role', $role);

        $request->request->set('roles', ['ROLE_ADMIN']);
        $this->userService->createUser($request);

        return new Response('Admin added successfully', Response::HTTP_CREATED);
    }

    public function __invokePUT(Request $request, $id): Response
    {
        try {
            $this->userService->updateUser($request, $id);
            return new Response('User updated successfully', Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response('Error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('ROLE_ENTITYNAME', message: 'You are not authorized to access this resource.')]
    #[Route("api/edit-password", methods: ["POST"], defaults: ['_api_resource_class' => User::class,], name: "edit_password")]
    public function upgradePassword(Request $request)
    {
        $password = $request->request->get('password');
        $newpassword = $request->request->get('newpassword');
        $data = json_decode($request->getContent(), true);

        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($data["email"]);

        if ($user) {
            if (!$this->passwordEncoder->isPasswordValid($user, $password)) {
                return $this->json(["message" => "l'ancien mot de passe incorrect !", "status" => false]);
            }
            $encodedPassword = $this->passwordEncoder->hashPassword($user, $newpassword);
            $this->entityManager->getRepository(User::class)->upgradePassword($user, $encodedPassword);

            return  $this->json(["message" => "Mot de passe changé", "status" => true]);
        } else {
            return $this->json(["message" => "Aucun d'utilisateur trouvé avec l'address e-mail !", "status" => false]);
        }
    }

    public function forgottenPassword(MailerInterface $mailer, Request $request, TokenGeneratorInterface $tokenGenerator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $result = $this->userService->handleForgotPassword($data["email"], $mailer, $tokenGenerator);

        return $this->json($result);
    }

    #[Route("reset_password/{token}", name: "reset_password")]
    public function resetPassword(string $token, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $result = $this->userService->handleResetPassword($token, $data["password"]);
        return $this->json($result);
    }
}
