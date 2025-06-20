<?php
// src/Form/RegistrationFormType.php

namespace App\Form\GestionUser;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class PatientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


        $builder

            ->add('relationshipStatus', ChoiceType::class, [
                'choices' => [
                    'Single' => 'Single',
                    'In a relationship' => 'In a relationship',
                    'Married' => 'Married',
                    'Divorced' => 'Divorced',
                    'Widowed' => 'Widowed',
                ],
                'expanded' => false,
                'multiple' => false,
                'placeholder' => 'Select a status',
                'required' => false
            ])
            ->add('religion', TextType::class, [
                'required' => false
            ])
            ->add('religionImportance', ChoiceType::class, [
                'choices' => [
                    'Very important' => 'Very important',
                    'Important' => 'Important',
                    'Somewhat important' => 'Somewhat important',
                    'Not important at all' => 'Not important at all',
                ],
                'expanded' => false,
                'multiple' => false,
                'placeholder' => 'Select importance level',
                'required' => false
            ])
            ->add('therapyExperience', ChoiceType::class, [
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
            ])
            ->add('therapyReasons', ChoiceType::class, [
                'choices' => [
                    'Anxiety' => 'Anxiety',
                    'Depression' => 'Depression',
                    'Family Issues' => 'Family Issues',
                    'Relationship Problems' => 'Relationship Problems',
                    'Self-Esteem Issues' => 'Self-Esteem Issues',
                    'Grief or Loss' => 'Grief or Loss',
                    'Career Stress' => 'Career Stress',
                    'Personal Growth' => 'Personal Growth',
                    'Other' => 'Other'
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false
            ])
            ->add('therapyType', ChoiceType::class, [
                'choices' => [
                    'Individual' => 'Individual',
                    'Couples' => 'Couples',
                    'Teen' => 'Teen'
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'placeholder' => 'Select therapy type',

            ])
        ;
    }

}
