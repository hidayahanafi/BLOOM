<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\LinkedInClient;
use League\OAuth2\Client\Provider\LinkedInUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LinkedInAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    private EntityManagerInterface $entityManager;
    private LinkedInClient $linkedinClient;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, LinkedInClient $linkedinClient, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->linkedinClient = $linkedinClient;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'login_linkedin_check';
    }

    public function authenticate(Request $request): Passport
    {
        // Fetch the access token
        $accessToken = $this->linkedinClient->getAccessToken();
        if (!$accessToken) {
            throw new AuthenticationException('No access token received from LinkedIn.');
        }

        /** @var LinkedInUser $linkedinUser */
        $linkedinUser = $this->linkedinClient->fetchUserFromToken($accessToken);
        $email = $linkedinUser->getEmail();

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($linkedinUser) {
                $userRepo = $this->entityManager->getRepository(User::class);
                $user = $userRepo->findOneBy(['email' => $linkedinUser->getEmail()]);

                if (!$user) {
                    // Create a new user if not found
                    $user = new User();
                    $user->setName($linkedinUser->getFirstName() . ' ' . $linkedinUser->getLastName());
                    $user->setEmail($linkedinUser->getEmail());
                    $user->setLinkedInId($linkedinUser->getId());
                    $user->setPassword(''); // No password required
                    $user->setRoles(["ROLE_USER", "ROLE_VERIFIED"]);
                    $user->setProfilePicture('assets/profilePics/default.png');
                    $user->setIsVerified(true); // Optional: adjust as needed
    
                    $this->entityManager->persist($user);
                } else {
                    // Update LinkedIn ID if missing
                    if (!$user->getLinkedInId()) {
                        $user->setLinkedInId($linkedinUser->getId());
                    }
                }

                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        return new RedirectResponse($this->urlGenerator->generate('user_profile')); // Redirect to the user profile page
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('login')); // Redirect to login on failure
    }
}
