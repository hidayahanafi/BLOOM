<?php

namespace App\Form\GestionObjectif;

use App\Entity\Recompense;
use App\Entity\Objectif;
use App\Entity\User; // Assure-toi d'inclure l'entité User
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class RecompenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la récompense',
                'required' => true,
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('cout', TextType::class, [
                'label' => 'Coût (points nécessaires)',
                'required' => false,
            ])
            ->add('etat', TextType::class, [
                'label' => 'État',
                'required' => true,
            ])
            ->add('img', TextType::class, [
                'label' => 'URL de l\'image',
                'required' => false,
            ])
            ->add('objectif', EntityType::class, [
                'class' => Objectif::class,
                'choice_label' => 'id',
                'label' => 'Sélectionner un objectif',
                'required' => false,
                'placeholder' => 'Choisissez un objectif',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                        ->where('NOT EXISTS (
                            SELECT 1 FROM App\Entity\Recompense r 
                            WHERE r.objectif = o.id
                        )');
                },
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,  // Associe ici l'entité User
                'choice_label' => 'id',  // Affiche le nom d'utilisateur dans la liste
                'label' => 'Sélectionner id utilisateur',
                'required' => false,  // Facultatif, à adapter selon tes besoins
                'placeholder' => 'Choisissez id utilisateur', // Option pour une valeur vide
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recompense::class,
        ]);
    }
}
