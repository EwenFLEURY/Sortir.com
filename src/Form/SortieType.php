<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie :',
                'required' => true,
            ])
            ->add('dateHeureDebut', null, [
                'label' => 'Date et heure de la sortie',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('dateLimiteInscription', null, [
                'label' => 'Date limite d\'inscription :',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('nbInscriptionMax', NumberType::class, [
                'label' => 'Nombre de places :',
                'required' => true,
                'html5' => true,
            ])
            ->add('duree', NumberType::class, [
                'label' => 'Durée :',
                'required' => true,
                'html5' => true,
            ])
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description et infos :',
                'required' => true,
            ])
            ->add('lieu', EntityType::class, [
                'label' => 'Lieu :',
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner un lieu',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
