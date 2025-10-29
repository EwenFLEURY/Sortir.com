<?php

namespace App\Security\Voter;

use App\Entity\Groupe;
use App\Entity\Ville;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class VilleVoter extends Voter
{
    public const EDIT   = 'VILLE_EDIT';
    public const VIEW   = 'VILLE_VIEW';
    public const ADD    = 'VILLE_ADD';
    public const DELETE = 'VILLE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::ADD, self::DELETE])
            && ($subject === null || $subject instanceof \App\Entity\User);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::ADD  => $this->canAdd($user),
            self::VIEW => $this->canView($user),
            self::EDIT => $this->canEdit($user),
            self::DELETE => $this->canDelete($user),
            default => false,
        };
    }

    private function canAdd(UserInterface $user): bool
    {
        return true;
    }

    private function canView(UserInterface $user): bool
    {
        return true;
    }

    private function canEdit( UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
