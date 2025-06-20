<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class BlockedUserSubscriber implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private RouterInterface $router;
    private RequestStack $requestStack;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        RouterInterface $router,
        RequestStack $requestStack
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ($user->getIsBlocked()) {
            // Log out the user
            $this->tokenStorage->setToken(null);

            $session = $this->requestStack->getSession();
            if ($session) {
                $session->invalidate();
                $session->getFlashBag()->add('error', 'Your account has been blocked by an administrator.');
            }

            // Redirect to login page
            $response = new RedirectResponse($this->router->generate('login'));
            $event->setResponse($response);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
}
