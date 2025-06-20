<?php

namespace App\Form;

use App\Entity\Cour;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class CourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('prix', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Théorique' => 'theorique',
                    'Pratique' => 'pratique'
                ],
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('Event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'titre',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('img', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cour::class,
        ]);
    }
}
