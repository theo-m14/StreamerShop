<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ref')
            ->add('title')
            ->add('imageFile', VichImageType::class, [
                'required' => false,
            ])
            ->add('price', NumberType::class, [
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('stock', NumberType::class, [
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('isVisible', CheckboxType::class, [
                'label' => 'Visible sur le site',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
