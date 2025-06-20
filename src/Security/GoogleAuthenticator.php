<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
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

class GoogleAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    private EntityManagerInterface $entityManager;
    private GoogleClient $googleClient;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, GoogleClient $googleClient, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->googleClient = $googleClient;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'login_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        // Fetch the access token
        $accessToken = $this->googleClient->getAccessToken();
        if (!$accessToken) {
            throw new AuthenticationException('No access token received from Google.');
        }

        /** @var GoogleUser $googleUser */
        $googleUser = $this->googleClient->fetchUserFromToken($accessToken);
        $email = $googleUser->getEmail();

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($googleUser) {
                $userRepo = $this->entityManager->getRepository(User::class);
                $user = $userRepo->findOneBy(['email' => $googleUser->getEmail()]);

                if (!$user) {
                    // Create a new user
                    $user = new User();
                    $user->setName($googleUser->getName());
                    $user->setEmail($googleUser->getEmail());
                    $user->setGoogleId($googleUser->getId());
                    $user->setPassword(''); // No password required
                    $user->setRoles(["ROLE_USER", "ROLE_VERIFIED"]);
                    $user->setProfilePicture('assets/profilePics/default.png');
                    $user->setIsVerified(true);


                    $this->entityManager->persist($user);
                } else {
                    // Update Google ID if missing
                    if (!$user->getGoogleId()) {
                        $user->setGoogleId($googleUser->getId());
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
        return new RedirectResponse($this->urlGenerator->generate('user_profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('login'));
    }
}
