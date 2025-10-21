<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActif()) {
            // Message affiché sur le formulaire de login
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est désactivé. Contactez un administrateur.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void {
        // Unused
    }
}