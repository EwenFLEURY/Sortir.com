<?php

namespace App\Form;

use App\Entity\Ville;
use Doctrine\DBAL\Types\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class VilleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextareaType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Nom',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                    ])
                ]
            ])
            ->add('codePostal', NumberType::class, [
                'label' => 'Code postal',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 5,
                        'max' => 5,
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ville::class,
        ]);
    }
}
