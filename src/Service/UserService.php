<?php

namespace App\Service;

use App\Entity\NewsEmail;
use App\Entity\User;
use App\Helpers\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Helpers $helpers,
        private ParameterBagInterface $parameters,
        private GlobalService $globalService
    ) {}

    private function convertDataTypes(array $data): array
    {
        if (isset($data['newsletter'])) {
            $data['newsletter'] = (bool)$data['newsletter'];
        }

        return $data;
    }

    public function createUser($request): User
    {
        if ($request->getMethod() == 'POST') {
            $user = new User();
            $allParts = $request->request->all();
            $allParts = $this->convertDataTypes($allParts);

            $user = $this->globalService->PersistEntityDenormalizer($allParts, $user::class);
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));

            if ($request->files->get("photo")) {
                $user->setFile($request->files->get("photo"));
            }

            if (isset($allParts['newsletter']) && $allParts['newsletter'] === true) {
                $newsletter = new NewsEmail();
                $newsletter->setEmail($allParts['email']);

                $this->entityManager->persist($newsletter);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $user;
        }

        throw new Exception("Invalid request method");
    }

    //% --------------------------------
    //% - Update User PUT
    //% --------------------------------
    public function updateUser($request, $userId): User
    {
        $parameters = $request->request->all();
        $content = $request->getContent();
        $files = $request->files->all();

        if (empty($parameters) && empty($content) && empty($files)) {
            throw new Exception("Request is empty");
        }

        if ($request->getMethod() == 'PUT') {
            $user = $this->entityManager->getRepository(User::class)->find($userId);

            if (!$user) {
                throw new Exception("User not found");
            }

            $allParts = Helpers::parseMultipartFormData($request);

            $userData = $allParts;
            $photo = $allParts['photo'] ?? null;
            if (isset($userData['photo'])) {
                unset($userData['photo']);
            }

            $this->globalService->UpdateEntityDenormalizer($user, $userData, $user::class);

            if (!empty($allParts["password"])) {
                $user->setPassword(password_hash($allParts["password"], PASSWORD_BCRYPT));
            }
            if ($photo && $photo instanceof UploadedFile) {
                $user->setFile($photo);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            return $user;
        }

        throw new Exception("Invalid request method or request data");
    }

    public function handleForgotPassword(string $email, MailerInterface $mailer, TokenGeneratorInterface $tokenGenerator): array
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        $fromEmail = 'noreply@tricodezone.com';
        $toEmail = 'mohammedbenseghir.online@gmail.com';

        if (!$user) {
            return ["message" => "No user found with this email address!", "status" => false];
        }

        $token = $tokenGenerator->generateToken();
        $user->setResetToken($token);
        $this->entityManager->flush();

        $url = $_ENV["APP_URL"] . '/reset_password/' . $token;

        $emailMessage = (new TemplatedEmail())
            ->from(new Address($fromEmail, 'TriCodeZone'))
            // ->to($toEmail)
            ->to($user->getEmail())
            ->subject('Password Reset Request')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context(['url' => $url, 'user' => $user]);

        $mailer->send($emailMessage);

        return ["message" => "Message sent. Please check your email.", "status" => true];
    }

    public function handleResetPassword(string $token, string $plainPassword): array
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            return ["message" => "Invalid token or user not found.", "status" => false];
        }

        $user->setResetToken('');
        $encodedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
        $user->setPassword($encodedPassword);

        $this->entityManager->flush();

        return ["message" => "Password successfully updated.", "status" => true];
    }
}
