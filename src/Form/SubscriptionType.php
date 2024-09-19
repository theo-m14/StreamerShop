<?php

namespace App\Form;

use App\Entity\Plan;
use App\Entity\user;
use App\Entity\Subscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPeriodStart', null, [
                'widget' => 'single_text',
                'label' => 'Date de début de la période',
            ])
            ->add('currentPeriodEnd', null, [
                'widget' => 'single_text',
                'label' => 'Date de fin de la période',
            ])
            ->add('plan', EntityType::class, [
                'class' => Plan::class,
                'choice_label' => 'name',
                'label' => 'Plan',
            ])
            ->add('user', EntityType::class, [
                'class' => user::class,
                'choice_label' => 'email',
                'label' => 'Utilisateur',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
        ]);
    }
}
