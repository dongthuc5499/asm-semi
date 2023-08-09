<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use App\Controller\AddressController;

class AddressType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('province', ChoiceType::class, [
                'label' => 'Province',
                'choices' => [
                    'Hà Nội' => 'Hà Nội',
                    'TP. Hồ Chí Minh' => 'TP. Hồ Chí Minh',
                    'Đà Nẵng' => 'Đà Nẵng',
                    // Thêm các tỉnh khác tại đây
                ],
                'attr' => ['class' => 'form-control select2'],
            ])
            ->add('district', TextType::class, [
                'label' => 'District',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('ward', TextType::class, [
                'label' => 'Ward',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('street', TextType::class, [
                'label' => 'Street',
                'attr' => ['class' => 'form-control'],
            ]);


        $currentUser = $this->security->getUser();
        $builder->add('user', EntityType::class, [
            'label' => 'User',
            'class' => 'App\Entity\User',
            'choice_label' => 'username',
            'data' => $currentUser,
            'disabled' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }

}