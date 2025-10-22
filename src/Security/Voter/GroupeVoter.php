<?php

namespace App\Security\Voter;

use App\Entity\Groupe;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class GroupeVoter extends Voter
{
    public const EDIT   = 'GROUP_EDIT';
    public const VIEW   = 'GROUP_VIEW';
    public const ADD    = 'GROUP__ADD';
    public const DELETE = 'GROUP_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::VIEW, self::ADD, self::DELETE], true)) {
            return false;
        }

        // Globaux : pas besoin de sujet
        if (in_array($attribute, [self::ADD, self::VIEW], true)) {
            return $subject === null || $subject instanceof Groupe;
        }

        // EDIT/DELETE nécessitent un Groupe
        return $subject instanceof Groupe;
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
            self::EDIT => $subject instanceof Groupe && $this->canEdit($subject, $user),
            self::DELETE => $subject instanceof Groupe && $this->canDelete($subject, $user),
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

    private function canEdit(Groupe $groupe, UserInterface $user): bool
    {
        return $groupe->getProprietaire() === $user;
    }

    private function canDelete(Groupe $groupe, UserInterface $user): bool
    {
        return $groupe->getProprietaire() === $user;
    }
}
