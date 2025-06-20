<?php

namespace App\Form;

use App\Entity\Planning;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PlanningType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $showDoctor = $options['show_doctor']; // Get option flag

        if ($showDoctor) {
            // Show the doctor field for admins
            $builder->add('doctor', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'label' => 'Doctor',
                'placeholder' => 'Select a doctor',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->andWhere('u.id NOT IN (SELECT IDENTITY(p.doctor) FROM App\Entity\Planning p)')
                        ->setParameter('role', '%ROLE_DOCTOR%');
                },
                'required' => true,
            ]);
        }

        $builder
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Start Date',
                'required' => true,
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'End Date',
                'required' => true,
            ])
            ->add('dailyStartTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Daily Start Time',
                'required' => true,
            ])
            ->add('dailyEndTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Daily End Time',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Planning::class,
            'show_doctor' => false, // Default to hiding doctor field
        ]);
    }
}
