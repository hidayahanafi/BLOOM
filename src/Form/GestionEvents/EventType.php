<?php

namespace App\Form;

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType; // Keep this import
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;





class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('titre', TextType::class, [
            'required' => false, // Désactiver la validation HTML
            'attr' => ['class' => 'form-control']
        ])
        ->add('description', TextareaType::class, [
            'required' => false,
            'attr' => ['class' => 'form-control']
        ])
        ->add('lieu', TextType::class, [
            'required' => false,
            'attr' => ['class' => 'form-control']
        ])
        ->add('date', DateType::class, [
            'widget' => 'single_text',
            'required' => false, // Allows the field to be optional
            'empty_data' => (new \DateTime())->format('Y-m-d'), // Ensures a valid date format
            'attr' => ['class' => 'form-control']
        ])
        ->add('heure', TextType::class, [
            'required' => false,
            'attr' => ['class' => 'form-control']
        ])
        
        
        ->add('type', ChoiceType::class, [
            'choices' => [
                'Conférence' => 'conférence',
                'Atelier' => 'atelier',
                'Séminaire' => 'séminaire'
            ],
            'required' => false,
            'attr' => ['class' => 'form-select']
        ])
        ->add('img', TextType::class, [
            'required' => false,
            'attr' => ['class' => 'form-control']
        ])
        ->add('budget', TextType::class, [
            'required' => false,
            'attr' => ['class' => 'form-control']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
