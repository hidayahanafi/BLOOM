<?php
namespace App\Form\GestionUser;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VerifyPhoneNumberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('verificationCode', TextType::class, [
            'label' => 'Enter the verification code sent to your phone',
            'attr' => [
                'placeholder' => 'Verification Code',
                'maxlength' => 6,
                'minlength' => 6,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
