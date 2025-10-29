<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class LieuVoter extends Voter
{
    public const CREATE = 'lieu_CREATE';
    public const VIEW = 'lieu_VIEW';
    public const EDIT = 'lieu_EDIT';
    public const DELETE = 'lieu_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE,])
            && ($subject === null || $subject instanceof \App\Entity\Lieu);
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
            self::VIEW => $this->canView($user),
            self::EDIT => $this->canEdit($user),
            self::DELETE => $this->canDelete($user),
            default => false,
        };
    }

    private function canCreate( UserInterface $user): bool
    {
        return true;
    }

    private function canView(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canEdit(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}

