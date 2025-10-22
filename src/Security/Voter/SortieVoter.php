<?php

namespace App\Security\Voter;

use App\Entity\Sortie;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class SortieVoter extends Voter
{
    public const CREATE = 'sortie_CREATE';
    public const VIEW = 'sortie_VIEW';
    public const VIEW_LIST = 'sortie_VIEW_LIST';
    public const EDIT = 'sortie_EDIT';
    public const CANCEL = 'sortie_CANCEL';
    public const SUBSCRIBE = 'sortie_Subscribe';
    public const DELETE = 'sortie_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
       return in_array($attribute, [self::CREATE, self::VIEW, self::VIEW_LIST, self::EDIT, self::CANCEL, self::SUBSCRIBE, self::DELETE])
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
            self::CREATE => $this->canCreate($user),
            self::VIEW => $this->canView($user),
            self::VIEW_LIST => $this->canViewList($user),
            self::EDIT => $this->canEdit($subject, $user),
            self::CANCEL => $this->canCancel($subject, $user),
            self::SUBSCRIBE => $this->canSubscribe($user),
            self::DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }

    private function canCreate( UserInterface $user): bool
    {
        return true;
    }

    private function canView(UserInterface $user): bool
    {
        return true;
    }

    private function canViewList( UserInterface $user): bool
    {
        return true;
    }

    private function canEdit(Sortie $sortie, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles()) || $user === $sortie->getOrganisateur();
    }

    private function canCancel(Sortie $sortie, UserInterface $user): bool{
        return $user === $sortie->getOrganisateur();
    }

    private function canSubscribe(UserInterface $user): bool
    {
        return true;
    }

    private function canDelete(Sortie $sortie, UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles()) || $user === $sortie->getOrganisateur();
    }
}
