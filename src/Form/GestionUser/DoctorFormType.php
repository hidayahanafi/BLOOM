<?php
namespace App\Form\GestionUser;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DoctorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('doctorType', ChoiceType::class, [
                'choices' => [
                    'Psychiatrist' => 'psychiatrist',
                    'Psychotherapist' => 'psychotherapist',
                    // Add more options as needed
                ],
                'placeholder' => 'Choose your specialty',
            ])
            ->add('specializations', ChoiceType::class, [
                'choices' => [
                    'Cognitive Behavioral Therapy (CBT)' => 'cbt',
                    'Trauma Therapy' => 'trauma_therapy',
                    'Depression Treatment' => 'depression_treatment',
                    'Anxiety Management' => 'anxiety_management',
                    'Couples Therapy' => 'couples_therapy',
                    'Child and Adolescent Therapy' => 'child_therapy',
                ],
                'multiple' => true,
                'expanded' => true,
                'label' => 'Specializations',
            ])
            ->add('experience', IntegerType::class, [
                'label' => 'Years of Practice',
                'required' => true,
            ])
            ->add('languagesSpoken', ChoiceType::class, [
                'choices' => [
                    'English' => 'english',
                    'French' => 'french',
                    'Spanish' => 'spanish',
                    'Arabic' => 'arabic',
                    'German' => 'german',
                ],
                'multiple' => true,
                'expanded' => true, // Display as checkboxes
                'label' => 'Languages Spoken',
            ])
            ->add('therapeuticApproaches', ChoiceType::class, [
                'choices' => [
                    'Cognitive Behavioral Therapy (CBT)' => 'cbt',
                    'Psychodynamic Therapy' => 'psychodynamic',
                    'Mindfulness-Based Therapy' => 'mindfulness',
                    'Humanistic Therapy' => 'humanistic',
                    'Integrative Therapy' => 'integrative',
                ],
                'multiple' => true,
                'expanded' => true, // Display as checkboxes
                'label' => 'Therapeutic Approaches',
            ])
            ->add('appointmentMethods', ChoiceType::class, [
                'choices' => [
                    'In-Person' => 'in_person',
                    'Online' => 'online',
                    'Hybrid' => 'hybrid',
                ],
                'multiple' => true,
                'expanded' => true, // Display as checkboxes
                'label' => 'Appointment Methods',
            ])
            ->add('diploma', FileType::class, [
                'label' => 'Upload your diploma (optional)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG) or PDF.',
                    ])
                ],
            ]);
    }


}
