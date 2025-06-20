<?php
namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\GithubClient;
use League\OAuth2\Client\Provider\GithubUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GithubAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    private EntityManagerInterface $entityManager;
    private GithubClient $githubClient;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        GithubClient $githubClient,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->githubClient = $githubClient;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'login_github_check';
    }

    public function authenticate(Request $request): Passport
    {
        // Fetch the access token
        $accessToken = $this->githubClient->getAccessToken();
        if (!$accessToken) {
            throw new AuthenticationException('No access token received from Github.');
        }

        /** @var GithubUser $githubUser */
        $githubUser = $this->githubClient->fetchUserFromToken($accessToken);
        $email = $githubUser->getEmail();

        // Use the GitHub username if the name is empty
        $name = $githubUser->getName();
        if (empty($name)) {
            $name = $githubUser->getNickname(); // Fallback to the GitHub username
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($githubUser, $name) {
                $userRepo = $this->entityManager->getRepository(User::class);
                $user = $userRepo->findOneBy(['email' => $githubUser->getEmail()]);

                if (!$user) {
                    // Create a new user
                    $user = new User();
                    $user->setName($name); // Use the name or username
                    $user->setEmail($githubUser->getEmail());
                    $user->setGithubId($githubUser->getId());
                    $user->setPassword(''); // No password required
                    $user->setRoles(["ROLE_USER", "ROLE_VERIFIED"]);
                    $user->setProfilePicture('assets/profilePics/default.png');
                    $user->setIsVerified(true);

                    $this->entityManager->persist($user);
                } else {
                    // Update Github ID if missing
                    if (!$user->getGithubId()) {
                        $user->setGithubId($githubUser->getId());
                    }
                }

                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect to the user's profile after successful authentication
        $user = $token->getUser();
        return new RedirectResponse($this->urlGenerator->generate('user_profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Redirect to login page in case of failure
        return new RedirectResponse($this->urlGenerator->generate('login'));
    }
}