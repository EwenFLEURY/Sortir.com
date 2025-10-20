<?php

namespace App\Entity\Enum;

enum Etat: string
{
    case Creee = 'Créée';
    case Ouverte = 'Ouverte';
    case Cloturee = 'Cloturée';
    case Activite = 'Activité en cours';
    case Passee = 'Passée';
    case Annulee = 'Annulée';
}