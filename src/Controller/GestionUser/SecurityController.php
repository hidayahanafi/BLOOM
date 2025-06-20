<?php

namespace App\Controller\GestionUser;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\Persistence\ManagerRegistry;

class SecurityController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private ManagerRegistry $doctrine;

    public function __construct(TokenStorageInterface $tokenStorage, ManagerRegistry $doctrine)
    {
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
    }

    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils, Security $security, Request $request): Response
    {
        // Uncomment if you want to block authenticated users from accessing the login page
        // if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
        //     return $this->redirectToRoute('user_profile');
        // }

        // Get login error if exists
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) {
            $this->addFlash('error', 'Invalid credentials. Please try again.');
        }

        return $this->render('gestion_user/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route("/connect/google", name: "connect_google")]
    public function connectGoogle(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect([
            'profile',
            'email'
        ], []);
    }

    #[Route("/login/check-google", name: "login_google_check")]
    public function loginCheckGoogle(): void
    {
        throw new \LogicException('This should be handled by Symfony security system.');
    }

    #[Route('/connect/github', name: 'connect_github')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('github')->redirect(
            ['user', 'email'],
            []
        );
    }

    #[Route('/connect/github/check', name: 'login_github_check')]
    public function connectCheck(Request $request): void
    {
        // This route is used by Symfony to handle the authentication callback.
        throw new \LogicException('This should never be reached.');
    }

    #[Route("/connect/linkedin", name: "connect_linkedin")]
    public function connectLinkedIn(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('linkedin')->redirect(['r_liteprofile', 'r_emailaddress'], []);
    }

    #[Route("/connect/linkedin/check", name: "login_linkedin_check")]
    public function loginCheckGithub(Request $request): void
    {
        // This route is used by Symfony to handle the authentication callback.
        throw new \LogicException('This should never be reached.');
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method will be intercepted by Symfony security.');
    }
}
