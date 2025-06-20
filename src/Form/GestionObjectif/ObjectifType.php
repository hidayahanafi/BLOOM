<?php

namespace App\Form\GestionObjectif;

use App\Entity\Objectif;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ObjectifType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'objectif',
                'required' => true, // Rend ce champ obligatoire
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => true, // Rend ce champ obligatoire

            ])
            ->add('date_debut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
                'required' => false, // Rendre facultatif
            ])
            ->add('date_fin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'required' => false, // Rendre facultatif
            ])
            ->add('statut', TextType::class, [
                'label' => 'Statut',
                'required' => true, // Rend ce champ obligatoire
            ])
            ->add('recompense', TextType::class, [
                'label' => 'Récompense',
                'required' => false, // Rendre facultatif
            ])
            ->add('NbPts', IntegerType::class, [
                'label' => 'Nombre de points',
                'required' => false, // Rendre facultatif
            ])
            ->add('img', TextType::class, [
                'label' => 'Image',
                'required' => false, // Rendre facultatif
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name', // Ou 'email' selon ce que tu veux afficher
                'placeholder' => 'aucun utilisateur',
                'required' => false, // Permettre que l'objectif puisse être sans user
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Objectif::class,
        ]);
    }
}
