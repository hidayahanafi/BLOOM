<?php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserVerificationVoter extends Voter
{
    // These are the attributes we want to check: 'VIEW' for access
    const VIEW = 'view';

    protected function supports(string $attribute, $subject): bool
    {
        // We only support 'VIEW' on User entities
        return $attribute === self::VIEW && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $user, TokenInterface $token): bool
    {
        // Check if the user is authenticated
        if (!$token->getUser() instanceof User) {
            return false;
        }

        // Now, check if the user is verified
        return $user->getIsVerified();
    }
}
