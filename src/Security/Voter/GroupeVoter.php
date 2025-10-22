<?php

namespace App\Security\Voter;

use App\Entity\Groupe;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class GroupeVoter extends Voter
{
    public const EDIT = 'POST_EDIT';
    public const VIEW = 'POST_VIEW';
    public const ADD = 'POST_ADD';
    public const DELETE = 'POST_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW, self::ADD, self::DELETE])
            && $subject instanceof \App\Entity\Groupe;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::ADD => $this->canAdd($user),
            self::VIEW => $this->canView($user),
            self::EDIT => $this->canEdit($subject,$user),
            self::DELETE => $this->canDelete($subject,$user),
            default => false,
        };
    }

    private function canAdd(UserInterface $user)
    {
        return true;
    }

    private function canView(UserInterface $user)
    {
        return true;
    }

    private function canEdit(Groupe $groupe,UserInterface $user)
    {
        return $groupe->getProprietaire() === $user;
    }

    private function canDelete(Groupe $groupe,UserInterface $user)
    {
        return $groupe->getProprietaire() === $user;
    }
}
