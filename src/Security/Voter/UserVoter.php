<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserVoter extends Voter
{
    public const CREATE = 'USER_CREATE';
    public const READ = 'USER_READ';
    public const EDIT = 'USER_EDIT';
    public const DISABLE = 'USER_DISABLE';
    public const DELETE = 'USER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::CREATE, self::READ, self::EDIT, self::DISABLE, self::DELETE])
            && ($subject === null || $subject instanceof \App\Entity\User);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($user),
            self::READ => $this->canRead($user),
            self::EDIT => $this->canEdit($subject, $user),
            self::DISABLE => $this->canDisable($user),
            self::DELETE => $this->canDelete($user),
            default => false,
        };
    }

    private function canCreate(UserInterface $userConnected): bool {
        return in_array('ROLE_ADMIN', $userConnected->getRoles());
    }

    private function canRead(UserInterface $userConnected): bool {
        return true;
    }

    private function canEdit(User $user, UserInterface $userConnected): bool {
        return $user === $userConnected;
    }

    private function canDisable(UserInterface $userConnected): bool {
        return in_array('ROLE_ADMIN', $userConnected->getRoles());
    }

    private function canDelete(UserInterface $userConnected): bool {
        return in_array('ROLE_ADMIN', $userConnected->getRoles());
    }
}
