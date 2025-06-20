<?php

namespace App\Controller\GestionUser;

use App\Entity\User;
use App\Form\GestionUser\ForgotPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password')]
    public function request(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailInput = $form->get('email')->getData();
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $emailInput]);

            if ($user) {
                // Generate a secure token
                $token = bin2hex(random_bytes(32));
                $user->setPasswordResetToken($token);
                $user->setPasswordResetRequestedAt(new \DateTime());

                $entityManager->flush();

                // Build the password reset URL
                $resetUrl = $urlGenerator->generate('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                // ...
                // Create and send the email
                $emailMessage = (new Email())
                    ->from('your_email@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Reset Your Password')
                    ->html(
                        $this->renderView('gestion_user/emails/reset_password_email.html.twig', [
                            'resetUrl' => $resetUrl,
                        ])
                    );
                $mailer->send($emailMessage);

            }

            // For security, always flash the same message regardless of whether the user exists
            $this->addFlash('success', 'If the email exists, a password reset link has been sent.');

            return $this->redirectToRoute('login');
        }

        return $this->render('gestion_user/security/forgot_password.html.twig', [
            'forgotPasswordForm' => $form->createView(),
        ]);
    }
}
