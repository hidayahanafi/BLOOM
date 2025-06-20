<?php

namespace App\Controller\GestionUser;

use App\Entity\User;
use App\Form\GestionUser\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password/{token}', name: 'reset_password')]
    public function reset(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        string $token
    ): Response {
        $user = $entityManager->getRepository(User::class)->findOneBy(['passwordResetToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Invalid or expired token.');
            return $this->redirectToRoute('login');
        }

        // Optional: Check token expiration using passwordResetRequestedAt

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);

            $user->setPassword($hashedPassword);
            $user->setPasswordResetToken(null);
            $user->setPasswordResetRequestedAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Your password has been reset successfully.');
            return $this->redirectToRoute('login');
        }

        return $this->render('gestion_user/security/reset_password.html.twig', [
            'resetPasswordForm' => $form->createView(),
        ]);

    }
}
