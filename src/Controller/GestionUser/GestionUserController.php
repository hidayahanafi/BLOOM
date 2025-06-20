<?php
// src/Controller/GestionUserController.php

namespace App\Controller\GestionUser;

use App\Entity\User;
use App\Form\GestionUser\RegistrationFormType;
use App\Form\GestionUser\DoctorRegistrationFormType;
use App\Form\GestionUser\RegisterFormType;
use App\Form\GestionUser\DoctorFormType;
use App\Form\GestionUser\PatientFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class GestionUserController extends AbstractController
{
    #[Route('/admin/user', name: 'gestion_user')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();
        return $this->render('admin/gestion_user/user.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/registration-choice', name: 'admin_registration_choice')]
    public function registrationChoice(): Response
    {
        return $this->render('admin/gestion_user/registration_choice.html.twig');
    }

    #[Route('/admin/user/create/{type}', name: 'admin_create_user')]
    public function create(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        string $type
    ): Response {
        $user = new User();

        // Choose the base form based on the type (doctor or patient)
        if ($type === 'doctor') {
            $form = $this->createForm(RegisterFormType::class, $user);
            $additionalForm = $this->createForm(DoctorFormType::class, $user);
        } else {
            $form = $this->createForm(RegisterFormType::class, $user);
            $additionalForm = $this->createForm(PatientFormType::class, $user);
        }

        // Handle the request for both forms
        $form->handleRequest($request);
        $additionalForm->handleRequest($request);

        // Check if both forms are submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $user->setPassword(
                $passwordHasher->hashPassword($user, $user->getPlainPassword())
            );

            // Generate a verification token if necessary
            $verificationToken = bin2hex(random_bytes(32));
            $user->setVerificationToken($verificationToken);
            // For admin-created users, auto-verify them
            $user->setIsVerified(true);

            // Set role and handle specific form logic for doctor or patient
            if ($type === 'doctor') {
                $user->setRoles(['ROLE_USER', 'ROLE_DOCTOR']);

                // Check if diploma is uploaded (optional field)
                $diplomaFile = $additionalForm->get('diploma')->getData();
                if ($diplomaFile) {
                    $newFilename = uniqid() . '.' . $diplomaFile->guessExtension();
                    try {
                        $diplomaFile->move(
                            $this->getParameter('diploma_directory'),
                            $newFilename
                        );
                        // Save the path to the diploma
                        $user->setDiploma('assets/diplomas/' . $newFilename);
                    } catch (\Exception $e) {
                        // Handle the error if necessary (log or show an error message)
                    }
                }
            } else {
                // For patient form, set role as ROLE_PATIENT
                $user->setRoles(['ROLE_USER', 'ROLE_PATIENT']);
            }

            // Persist the user and additional form data to the database
            $entityManager->persist($user);
            $entityManager->flush();

            // Success message and redirect to the user management page
            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('gestion_user');
        }

        // Render different templates based on the type (doctor or patient)
        if ($type === 'doctor') {
            return $this->render('admin/gestion_user/create_doctor.html.twig', [
                'registrationForm' => $form->createView(),
                'doctorForm' => $additionalForm->createView(),
            ]);
        } else {
            return $this->render('admin/gestion_user/create_patient.html.twig', [
                'registrationForm' => $form->createView(),
                'patientForm' => $additionalForm->createView(),
            ]);
        }
    }



    #[Route('/admin/profile/edit/{id}', name: 'user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editUserProfile(int $id, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        // Fetch the user by ID
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        // Create the basic form for user information
        $form = $this->createForm(RegisterFormType::class, $user, [
            'is_edit' => true,
            'validation_groups' => ['Default'],
        ]);

        // Determine which role-specific form to show based on the edited user's roles
        $extraForm = null;
        if (in_array('ROLE_DOCTOR', $user->getRoles())) {
            $extraForm = $this->createForm(DoctorFormType::class, $user);
        } elseif (in_array('ROLE_PATIENT', $user->getRoles())) {
            $extraForm = $this->createForm(PatientFormType::class, $user);
        }

        $form->handleRequest($request);
        if ($extraForm) {
            $extraForm->handleRequest($request);
        }

        // Validate both forms before saving
        if ($form->isSubmitted() && $form->isValid() && (!$extraForm || ($extraForm && $extraForm->isSubmitted() && $extraForm->isValid()))) {
            $entityManager->flush();
            $this->addFlash('success', 'Le profil a été mis à jour avec succès.');
            return $this->redirectToRoute('gestion_user');
        }


        return $this->render('admin/gestion_user/edit_user.html.twig', [
            'form' => $form->createView(),
            'extraForm' => $extraForm ? $extraForm->createView() : null,
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}/delete', name: 'admin_delete_user', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'User deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('gestion_user');
    }

    #[Route('/admin/user/block/{id}', name: 'admin_block_user')]
    public function blockUser(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Find the user by their ID
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Check the CSRF token for security
        if (!$this->isCsrfTokenValid('block' . $user->getId(), $request->request->get('_token'))) {
            throw new \Exception('Invalid CSRF token');
        }

        // Toggle the block status
        $user->setIsBlocked(!$user->getIsBlocked());

        // Persist the changes to the database
        $entityManager->persist($user);
        $entityManager->flush();

        // Add a flash message to notify the admin of the action
        $status = $user->getIsBlocked() ? 'blocked' : 'unblocked';

        // Redirect back to the user management page
        return $this->redirectToRoute('gestion_user'); // or the route where the users are listed
    }




}
