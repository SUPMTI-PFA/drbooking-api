<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\DoctorProfile;
use App\Entity\NewsEmail;
use App\Entity\Speciality;
use App\Enum\AccountType;
use App\Helpers\Helpers;
use App\Util\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Exception;
use DateTimeImmutable;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GlobalService $globalService,
        private SlugGenerator $slugGenerator,
        private ParameterBagInterface $parameters,
    ) {}

    private function convertDataTypes(array $data): array
    {
        if (isset($data['newsletter'])) {
            $data['newsletter'] = (bool)$data['newsletter'];
        }
        return $data;
    }

    //% --------------------------------
    //% - Handle Doctor Profile
    //% --------------------------------
    private function handleDoctorProfile(User $user, array $doctorData, bool $isUpdate = false): void
    {
        if ($user->getAccountType() !== AccountType::DOCTOR) {
            return;
        }

        $doctorProfile = $isUpdate ? $user->getDoctorProfile() : new DoctorProfile();

        if (!$doctorProfile) {
            $doctorProfile = new DoctorProfile();
            // dd($user);
            $doctorProfile->setUser($user);
            $doctorProfile->setIsActive(true);
        }

        // Update entity using denormalizer
        $this->globalService->UpdateEntityDenormalizer($doctorProfile, $doctorData, DoctorProfile::class);
        $doctorProfile->setUser($user);

        // Special handling for speciality relationship
        // if (isset($doctorData['speciality'])) {
        //     $speciality = $this->entityManager
        //         ->getRepository(Speciality::class)
        //         ->find($doctorData['speciality']);

        //     if (!$speciality) {
        //         throw new Exception("Speciality not found");
        //     }
        //     $doctorProfile->setSpeciality($speciality);
        // }

        if (!$isUpdate || !$doctorProfile->getId()) {
            $this->entityManager->persist($doctorProfile);
        }
    }

    //% --------------------------------
    //% - Handle Newsletter
    //% --------------------------------
    private function handleNewsletter(string $email, bool $subscribe): void
    {
        if ($subscribe) {
            $newsletter = new NewsEmail();
            $this->globalService->UpdateEntityDenormalizer(
                $newsletter,
                ['email' => $email],
                NewsEmail::class
            );
            $this->entityManager->persist($newsletter);
        }
    }

    //% --------------------------------
    //% - Create User
    //% --------------------------------
    public function createUser(Request $request): User
    {
        if ($request->getMethod() !== 'POST') {
            throw new Exception("Invalid request method");
        }

        $data = $request->request->all();
        $data = $this->convertDataTypes($data);
        $doctorData = $data['doctorProfile'] ?? [];

        $keysToUnset = [
            'doctorProfile'
        ];

        foreach ($keysToUnset as $key) {
            unset($data[$key]);
        }

        // Validate accountType
        $accountTypeValue = $data['accountType'] ?? null;
        if (!$accountTypeValue || !AccountType::tryFrom($accountTypeValue)) {
            throw new Exception("Invalid or missing accountType (PATIENT | DOCTOR)");
        }

        // /** @var User $user */
        $user = $this->globalService->PersistEntityDenormalizer($data, User::class);
        $fullName = $data['firstName'] . ' ' . $data['lastName'];

        $slug = $this->slugGenerator->generateSlug($fullName,  User::class);
        $user->setSlug($slug);
        $user->setfullName($fullName);

        // Special handling for password and account type
        $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
        $user->setAccountType(AccountType::from($accountTypeValue));

        // Handle profile photo
        $photoFile = $request->files->get('photo');
        if ($photoFile instanceof UploadedFile) {
            $user->setFile($photoFile);
        }

        // Handle newsletter
        if (!empty($data['newsletter'])) {
            $this->handleNewsletter($user->getEmail(), true);
        }

        // Handle doctor profile
        if ($user->getAccountType() === AccountType::DOCTOR) {
            if (empty($doctorData['speciality']) || empty($doctorData['address'])) {
                throw new Exception("DoctorProfile data (speciality, address) required for DOCTOR");
            }
            $this->handleDoctorProfile($user, $doctorData);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    //% --------------------------------
    //% - Update User
    //% --------------------------------
    public function updateUser(Request $request, int $userId): User
    {
        if ($request->getMethod() !== 'PUT') {
            throw new Exception("Invalid request method");
        }

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new Exception("User not found");
        }

        $parts = Helpers::parseMultipartFormData($request);
        $parts = $this->convertDataTypes($parts);

        // Update base fields using denormalizer
        $this->globalService->UpdateEntityDenormalizer($user, $parts, User::class);

        // Special handling for password and account type
        if (!empty($parts['accountType']) && AccountType::tryFrom($parts['accountType'])) {
            $user->setAccountType(AccountType::from($parts['accountType']));
        }

        if (!empty($parts['password'])) {
            $user->setPassword(password_hash($parts['password'], PASSWORD_BCRYPT));
        }

        // Photo update
        $photoFile = $request->files->get('photo');
        if ($photoFile instanceof UploadedFile) {
            $user->setFile($photoFile);
        }

        // Handle doctor profile
        if ($user->getAccountType() === AccountType::DOCTOR && !empty($parts['doctorProfile'])) {
            $this->handleDoctorProfile($user, $parts['doctorProfile'], true);
        }

        $user->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $user;
    }

    //% --------------------------------
    //% - Handle Forgot Password
    //% --------------------------------
    public function handleForgotPassword(
        string $email,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator
    ): array {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if (!$user) {
            return ['status' => false, 'message' => 'No user found with this email'];
        }

        $token = $tokenGenerator->generateToken();
        $user->setResetToken($token);
        $this->entityManager->flush();

        $resetUrl = $_ENV['APP_URL'] . '/reset_password/' . $token;
        $emailMessage = (new TemplatedEmail())
            ->from(new Address('noreply@tricodezone.com', 'TriCodeZone'))
            ->to($user->getEmail())
            ->subject('Password Reset Request')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context(['url' => $resetUrl, 'user' => $user]);

        $mailer->send($emailMessage);

        return ['status' => true, 'message' => 'Reset email sent'];
    }

    //% --------------------------------
    //% - Handle Reset Password
    //% --------------------------------
    public function handleResetPassword(string $token, string $newPassword): array
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['resetToken' => $token]);

        if (!$user) {
            return ['status' => false, 'message' => 'Invalid token'];
        }

        $user->setResetToken(null);
        $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        $this->entityManager->flush();

        return ['status' => true, 'message' => 'Password updated successfully'];
    }
}
