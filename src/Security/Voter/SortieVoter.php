<?php

namespace App\Security\Voter;

use App\Entity\Sortie;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class SortieVoter extends Voter
{
    public const EDIT = 'sortie_EDIT';
    public const VIEW = 'sortie_VIEW';
    public const DELETE = 'sortie_DELETE';
    public const CREATE = 'sortie_CREATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::CREATE])
            && ($subject === null || $subject instanceof \App\Entity\Sortie);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::VIEW => $this->canView($user),
            self::CREATE => $this->canCreate($user),
            self::DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }
    private function canEdit(Sortie $sortie, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles()) || $user === $sortie->getOrganisateur();
    }
    private function canView(UserInterface $user): bool
    {
        return true;
    }
    private function canCreate( UserInterface $user): bool
    {
        return true;
    }
    private function canDelete(Sortie $sortie, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles()) || $user === $sortie->getOrganisateur();
    }
}
