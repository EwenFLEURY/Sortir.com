<?php

namespace App\Form;

use App\Entity\Groupe;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class GroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255]),
                ],
                'attr' => [
                    'placeholder' => 'Nom du groupe',
                ],
                'required' => true,
            ])
            ->add('proprietaire', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'nom',
                'data' => $options['user'],
                'constraints' => [
                    new NotBlank([])
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ajouter un utilisateur'
                ]
            ])
            ->add('members', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
                'constraints' => [
                    new NotBlank(),
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Membre'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Groupe::class,
            'user' => null,
        ]);
    }
}
