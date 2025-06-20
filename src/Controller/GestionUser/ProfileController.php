<?php

namespace App\Controller\GestionUser;

use App\Entity\User;
use App\Entity\UserFace;
use App\Form\GestionUser\RegisterFormType;
use App\Form\GestionUser\DoctorFormType;
use App\Form\GestionUser\PatientFormType;
use App\Form\GestionUser\VerifyPhoneNumberType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginFormAuthenticator;
use App\Security\GoogleAuthenticator;
use App\Security\GithubAuthenticator;
use App\Security\LinkedInAuthenticator;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Twilio\Rest\Client;
use Doctrine\Persistence\ManagerRegistry;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'user_profile')]
    public function profile(Security $security, ManagerRegistry $doctrine): Response
    {
        $user = $security->getUser();

        // Retrieve the user's face registration from the separate table.
        $faceRegistration = $doctrine->getRepository(UserFace::class)
            ->findOneBy(['userId' => $user->getId()]);
        $hasFaceRegistration = ($faceRegistration !== null);

        $completionSteps = [
            [
                'name' => 'Verify your email',
                'completed' => $user->getIsVerified(), // Check if the email is verified
                'url' => $this->generateUrl('request_email_verification')
            ],
            [
                'name' => 'Fill your profile information',
                'completed' => ($user->getIsVerified() && (in_array('ROLE_DOCTOR', $user->getRoles()) || in_array('ROLE_PATIENT', $user->getRoles()))),
                'url' => $this->generateUrl('user_profile_edit')
            ],
            [
                'name' => 'Add Phone Number',
                'completed' => !empty($user->getPhoneNumber()),
                'url' => $this->generateUrl('user_profile')
            ],
            [
                'name' => 'Verify Phone Number',
                'completed' => $user->getIsPhoneVerified(),
                'url' => $this->generateUrl('send_verification_sms')
            ],
            [
                'name' => 'Register Your Face',
                'completed' => $hasFaceRegistration,  // Now uses the value from your separate face table
                'url' => $this->generateUrl('face_register')
            ],
        ];

        return $this->render('gestion_user/profile/profile.html.twig', [
            'user' => $user,
            'completionSteps' => $completionSteps,
            'hasFaceRegistration' => $hasFaceRegistration,
        ]);
    }




    #[Route('/profile/edit', name: 'user_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Security $security,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $loginFormAuthenticator,
        GoogleAuthenticator $googleAuthenticator,
        LinkedInAuthenticator $linkedInAuthenticator, // Add LinkedInAuthenticator
        GitHubAuthenticator $gitHubAuthenticator // Add GitHubAuthenticator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $form = $this->createForm(RegisterFormType::class, $user, [
            'is_edit' => true,
            'validation_groups' => ['Default'],
        ]);

        $doctorForm = $this->createForm(DoctorFormType::class, $user);
        $patientForm = $this->createForm(PatientFormType::class, $user);

        $doctorForm->handleRequest($request);

        if ($doctorForm->isSubmitted() && $doctorForm->isValid()) {
            $roles = $user->getRoles();
            $roles[] = 'ROLE_DOCTOR';
            $user->setRoles(array_unique($roles));

            $em->flush();

            return $this->reAuthenticateUser($user, $request, $userAuthenticator, $loginFormAuthenticator, $googleAuthenticator, $linkedInAuthenticator, $gitHubAuthenticator);
        }

        $patientForm->handleRequest($request);

        if ($patientForm->isSubmitted() && $patientForm->isValid()) {
            $roles = $user->getRoles();
            $roles[] = 'ROLE_PATIENT';
            $user->setRoles(array_unique($roles));

            $em->flush();

            return $this->reAuthenticateUser($user, $request, $userAuthenticator, $loginFormAuthenticator, $googleAuthenticator, $linkedInAuthenticator, $gitHubAuthenticator);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getPlainPassword()) {
                $user->setPassword($passwordHasher->hashPassword($user, $user->getPlainPassword()));
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated successfully!');

            return $this->reAuthenticateUser($user, $request, $userAuthenticator, $loginFormAuthenticator, $googleAuthenticator, $linkedInAuthenticator, $gitHubAuthenticator);
        }

        return $this->render('gestion_user/profile/edit.html.twig', [
            'form' => $form->createView(),
            'doctorForm' => $doctorForm->createView(),
            'patientForm' => $patientForm->createView(),
        ]);
    }


    private function reAuthenticateUser(
        $user,
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $loginFormAuthenticator,
        GoogleAuthenticator $googleAuthenticator,
        LinkedInAuthenticator $linkedInAuthenticator, // Add LinkedInAuthenticator
        GitHubAuthenticator $gitHubAuthenticator // Add GitHubAuthenticator
    ): Response {
        // Check if the user was authenticated via Google
        if (in_array('ROLE_OAUTH_GOOGLE', $user->getRoles())) {
            return $userAuthenticator->authenticateUser($user, $googleAuthenticator, $request);
        }

        // Check if the user was authenticated via LinkedIn
        if (in_array('ROLE_OAUTH_LINKEDIN', $user->getRoles())) {
            return $userAuthenticator->authenticateUser($user, $linkedInAuthenticator, $request);
        }

        // Check if the user was authenticated via GitHub
        if (in_array('ROLE_OAUTH_GITHUB', $user->getRoles())) {
            return $userAuthenticator->authenticateUser($user, $gitHubAuthenticator, $request);
        }

        // Otherwise, use login form authentication
        return $userAuthenticator->authenticateUser($user, $loginFormAuthenticator, $request);
    }





    #[Route('/delete-account', name: 'user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteUser(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('login');
        }

        $tokenStorage->setToken(null);
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Your account has been deleted.');

        return $this->redirectToRoute('login');
    }


    #[Route('/profile/request-verification', name: 'request_email_verification')]
    public function requestEmailVerification(MailerInterface $mailer, UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('user_profile');
        }

        // Generate a new verification token
        $verificationToken = bin2hex(random_bytes(32));
        $user->setVerificationToken($verificationToken);

        $entityManager->persist($user);
        $entityManager->flush();

        // Generate the verification URL
        $verificationUrl = $urlGenerator->generate(
            'verify_email',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Send the verification email
        $email = (new Email())
            ->from('your_email@gmail.com')
            ->to($user->getEmail())
            ->subject('Verify Your Email Address')
            ->html(
                $this->renderView('gestion_user/registration/verification_email.html.twig', [
                    'verificationUrl' => $verificationUrl,
                    'user' => $user,
                ])
            );

        $mailer->send($email);

        $this->addFlash('success', 'A verification email has been sent to your email address.');

        return $this->redirectToRoute('user_profile');
    }

    #[Route('/verify/{token}', name: 'verify_email')]
    public function verifyEmail(string $token, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $logger->info('Verification token received: ' . $token);
        $user = $entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $logger->error('Invalid verification token: ' . $token);
            $this->addFlash('error', 'Invalid verification token.');
            return $this->redirectToRoute('user_profile');
        }

        $roles = $user->getRoles();
        $roles[] = 'ROLE_VERIFIED';
        $user->setRoles(array_unique($roles));

        $user->setIsVerified(true);
        $user->setVerificationToken(null);

        $entityManager->flush();

        $this->addFlash('success', 'Your email has been verified successfully.');

        return $this->redirectToRoute('user_profile');
    }

    #[Route('/profile/upload', name: 'profile_picture_upload', methods: ['POST'])]
    public function uploadProfilePicture(Request $request, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Invalid user.');
        }

        // Get the upload directories
        $profileUploadsDir = $this->getParameter('kernel.project_dir') . '/public/assets/profilePics';
        $coverUploadsDir = $this->getParameter('kernel.project_dir') . '/public/assets/coverPics';

        // Handle Profile Picture Upload
        $profileFile = $request->files->get('profilePicture');
        if ($profileFile) {
            // Delete old profile picture if it exists
            if ($user->getProfilePicture()) {
                $oldProfilePath = $this->getParameter('kernel.project_dir') . '/public' . $user->getProfilePicture();
                if (file_exists($oldProfilePath)) {
                    unlink($oldProfilePath);
                }
            }

            // Generate new file name and move file
            $profileFilename = $user->getId() . '_' . str_replace(' ', '_', $user->getName()) . '.' . $profileFile->guessExtension();
            try {
                $profileFile->move($profileUploadsDir, $profileFilename);
                $user->setProfilePicture('/assets/profilePics/' . $profileFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload profile picture.');
            }
        }

        // Handle Cover Picture Upload
        $coverFile = $request->files->get('coverPicture');
        if ($coverFile) {
            // Delete old cover picture if it exists
            if ($user->getCoverPicture()) {
                $oldCoverPath = $this->getParameter('kernel.project_dir') . '/public' . $user->getCoverPicture();
                if (file_exists($oldCoverPath)) {
                    unlink($oldCoverPath);
                }
            }

            // Generate new file name and move file
            $coverFilename = $user->getId() . '_' . str_replace(' ', '_', $user->getName()) . '.' . $coverFile->guessExtension();
            try {
                $coverFile->move($coverUploadsDir, $coverFilename);
                $user->setCoverPicture('/assets/coverPics/' . $coverFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload cover picture.');
            }
        }

        // Save changes if any file was uploaded
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Profile updated successfully!');

        // Redirect based on user role
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_profile'); // Adjust as needed
        }

        return $this->redirectToRoute('user_profile'); // Adjust the route if necessary
    }


    // Add the method to send the verification SMS
    #[Route('/profile/send-verification-sms', name: 'send_verification_sms')]
    public function sendPhoneNumberVerification(
        Request $request,
        EntityManagerInterface $entityManager,
        Client $twilioClient
    ): Response {
        $user = $this->getUser();

        if (!$user || empty($user->getPhoneNumber())) {
            $this->addFlash('error', 'No phone number found to verify.');
            return $this->redirectToRoute('user_profile');
        }

        // Generate a random verification code
        $verificationCode = rand(100000, 999999); // 6-digit code

        // Save the verification code to the user (you should add a field in the User entity for storing this)
        $user->setPhoneVerificationCode($verificationCode);
        $entityManager->flush();

        try {
            $message = sprintf(
                'Your phone number verification code is: %d.',
                $verificationCode
            );

            // Format phone number (adjust country code if needed)
            $numeroLocal = $user->getPhoneNumber();
            $codePays = '216'; // Tunisia country code
            $to = '+' . $codePays . $numeroLocal;

            // Send SMS using Twilio
            $twilioClient->messages->create(
                $to,
                [
                    'from' => $_ENV['TWILIO_PHONE_NUMBER'],
                    'body' => $message,
                ]
            );

            $this->addFlash('success', 'A verification code has been sent to your phone number.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Failed to send verification SMS.');
        }

        // Redirect to verification form page
        return $this->redirectToRoute('verify_phone_number');
    }



    // Route to verify the phone number with the code
    #[Route('/profile/verify-phone', name: 'verify_phone_number', methods: ['GET', 'POST'])]
    public function verifyPhoneNumber(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('user_profile');
        }

        $form = $this->createForm(VerifyPhoneNumberType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $verificationCode = $form->get('verificationCode')->getData();

            if ($verificationCode == $user->getPhoneVerificationCode()) {
                // Verification successful
                $user->setIsPhoneVerified(true);
                $user->setPhoneVerificationCode(null); // Clear the code after verification
                $entityManager->flush();

                $this->addFlash('success', 'Your phone number has been verified successfully.');
                return $this->redirectToRoute('user_profile');

            } else {
                $this->addFlash('error', 'Invalid verification code.');
                return $this->redirectToRoute('home');

            }

        }

        return $this->render('gestion_user/profile/verify_phone.html.twig', [
            'form' => $form->createView(),
        ]);
    }












    // ========================== ADMIN METHODS ==========================

    #[Route('/admin/profile', name: 'admin_profile')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminProfile(): Response
    {
        return $this->render('admin/gestion_user/profile.html.twig');
    }






    #[Route('/admin/profile/edit', name: 'admin_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminEdit(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $admin = $this->getUser();
        if (!$admin) {
            throw $this->createNotFoundException('Admin not found.');
        }

        // Create the form
        $form = $this->createForm(RegisterFormType::class, $admin, [
            'is_edit' => true,
            'validation_groups' => ['Default'],
        ]);

        // Add the password field for editing (allowing it to be empty)
        $form->remove('plainPassword');
        $form->add('plainPassword', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, [
            'required' => false,
            'label' => 'New password (leave empty if unchanged)',
            'attr' => ['class' => 'form-control'],
            'mapped' => true,
            'constraints' => [],
        ]);

        // Handle the form submission
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            dump($form->getErrors(true));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // If a new password is provided, hash and set it
            if ($admin->getPlainPassword()) {
                $admin->setPassword(
                    $passwordHasher->hashPassword($admin, $admin->getPlainPassword())
                );
            }

            // Save changes to the admin's profile
            $em->flush();

            // Show a success message and redirect
            $this->addFlash('success', 'Admin profile updated successfully!');

            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('admin/gestion_user/editProfile.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/admin/delete-account', name: 'admin_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAdmin(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): Response
    {
        $admin = $this->getUser();

        if (!$admin) {
            $this->addFlash('error', 'Admin not found.');
            return $this->redirectToRoute('admin_profile');
        }

        $tokenStorage->setToken(null);
        $entityManager->remove($admin);
        $entityManager->flush();

        $this->addFlash('success', 'Admin account deleted.');

        return $this->redirectToRoute('login');
    }




}
