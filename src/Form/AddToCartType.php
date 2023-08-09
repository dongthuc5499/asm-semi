<?php

namespace App\Form;

use App\Entity\OrderItem;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


class AddToCartType extends AbstractType
{
//    private Security $security;
//
//    public function __construct(Security $security)
//    {
//        $this->security = $security;
//    }
//
//    public function buildForm(FormBuilderInterface $builder, array $options)
//    {
//        $builder->add('quantity');
//        $builder->add('add', SubmitType::class, [
//            'label' => 'Add to cart'
//        ]);
//
//        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
//            $form = $event->getForm();
//            $orderItem = $event->getData();
//            $user = $this->security->getUser();
//
//            if ($user instanceof User && $orderItem !== null) {
//                $orderItem->setUser($user);
//            }
//
//            $event->setData($orderItem);
//        });
//    }
//
//    public function configureOptions(OptionsResolver $resolver)
//    {
//        $resolver->setDefaults([
//            'data_class' => OrderItem::class,
//            'user' => null,
//        ]);
//    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('quantity');
        $builder->add('add', SubmitType::class, [
            'label' => 'Add to cart'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}



