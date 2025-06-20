<?php

namespace App\Form\GestionPlanning;

use App\Entity\Appointment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Retrieve the planning object from options.
        $planning = $options['planning'] ?? null;
        if (!$planning) {
            throw new \InvalidArgumentException('A Planning object must be provided.');
        }

        // Add an appointment date field (unmapped, used only for user input)
        $builder->add('appointmentDate', DateType::class, [
            'widget' => 'single_text',
            'mapped' => false,
            'label' => 'Choose Appointment Date',
            'required' => true,
        ]);

        // Add a time slot field (unmapped) with initially empty choices.
        // This field will be updated by AJAX on the client side.
        $builder->add('startAt', ChoiceType::class, [
            'choices' => [],  // Initially empty – choices will be updated via AJAX.
            'mapped' => false,  // We'll combine its value with appointmentDate in the controller.
            'placeholder' => 'Select a time slot',
            'label' => 'Choose a time slot',
            'required' => true,
        ]);

        // Add a PRE_SUBMIT listener to add the submitted value as a valid choice
        // so that server-side validation passes.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            if (isset($data['startAt']) && !empty($data['startAt'])) {
                $submittedTime = $data['startAt'];
                // Re-add the field with the submitted value as its only choice.
                $form->add('startAt', ChoiceType::class, [
                    'choices' => [$submittedTime => $submittedTime],
                    'mapped' => false,
                    'placeholder' => 'Select a time slot',
                    'label' => 'Choose a time slot',
                    'required' => true,
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'planning' => null,
        ]);
        $resolver->setRequired('planning');
    }
}
