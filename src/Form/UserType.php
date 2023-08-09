<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter username',
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter phone number',
                ],
            ])
            ->add('sex', ChoiceType::class, [
                'choices' => [
                    'Male' => 'male',
                    'Female' => 'female',
                ],
                'placeholder' => 'Choose your gender',
            ])
            ->add('birthday', BirthdayType::class, [
                'label' => 'Birthday',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter birthday',
                ],
                'input' => 'datetime',
                'html5' => false,
                'format' => 'dd/MM/yyyy', // Định dạng ngày/tháng/năm ở đây
            ])
            ->add('avatarImage', FileType::class, [
                'label' => 'Image file',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypesMessage' => 'Please upload a valid image',
                    ])
                ],
            ]);
//            ->add('plainPassword', PasswordType::class, [
//                'label' => 'Password',
//                'mapped' => false,
//                'required' => false,
//                'attr' => [
//                    'class' => 'form-control',
//                    'placeholder' => 'Password',
//                ],
//            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
