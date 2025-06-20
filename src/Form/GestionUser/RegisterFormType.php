<?php
// src/Form/RegistrationFormType.php

namespace App\Form\GestionUser;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class RegisterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Determine if we are in edit mode.
        $isEdit = $options['is_edit'];

        $plainPasswordOptions = [
            'mapped' => true,
            'attr' => ['autocomplete' => 'new-password'],
        ];

        if (!$isEdit) {
            // Registration: password is required with constraints.
            $plainPasswordOptions['constraints'] = [
                new NotBlank(['message' => 'Password cannot be blank.']),
                new Length([
                    'min' => 6,
                    'minMessage' => 'Password must be at least 6 characters long.',
                ]),
            ];
        } else {
            // Editing: password is optional.
            $plainPasswordOptions['required'] = false;
            $plainPasswordOptions['attr']['placeholder'] = 'Laisser vide si inchangé';
            // Remove constraints.
            $plainPasswordOptions['constraints'] = [];
        }

        $builder
            ->add('email', EmailType::class)
            ->add('name', TextType::class)
            ->add('age', IntegerType::class, [
                'required' => false,
            ])
            ->add('phoneNumber', TextType::class, [
                'required' => false,
            ])
            ->add('country', TextType::class, [
                'required' => false,
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, $plainPasswordOptions)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'validation_groups' => ['Default', 'Registration'],
        ]);
    }
}
