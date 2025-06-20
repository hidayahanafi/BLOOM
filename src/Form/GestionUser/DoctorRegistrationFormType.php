<?php
namespace App\Form\GestionUser;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DoctorRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('plainPassword', PasswordType::class, [
                // Maps to the plainPassword property in the User entity
            ])
            ->add('doctorType', ChoiceType::class, [
                'choices' => [
                    'Psychiatrist' => 'psychiatrist',
                    'Psychotherapist' => 'psychotherapist',
                    // Add more options as needed
                ],
                'placeholder' => 'Choose your specialty',
            ])
            ->add('diploma', FileType::class, [
                'label' => 'Upload your diploma (optional)',
                'mapped' => false, // We'll handle the file manually in the controller
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG).',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'validation_groups' => ['Default', 'Registration'],
        ]);
    }
}
