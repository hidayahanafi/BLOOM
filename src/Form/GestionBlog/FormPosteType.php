<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class FormPosteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Title',
                'attr' => ['placeholder' => 'Enter your post title']
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Content',
                'attr' => [
                    'placeholder' => 'Write your post content here...',
                    'rows' => '6'
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Anxiété & Stress' => 'Anxiété & Stress',
                    'Développement Personnel' => 'Développement Personnel',
                    'Dépression & Bien-être Émotionnel' => 'Dépression & Bien-être Émotionnel', 
                    'Troubles Psychologiques' => 'Troubles Psychologiques',
                    'Techniques & Thérapies' => 'Techniques & Thérapies',
                ],
                'placeholder' => 'Select a category'
            ])
            ->add('image_post', FileType::class, [
                'label' => 'Featured Image',
                'required' => false,
                'mapped' => true,
                'data_class' => null,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF)'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
