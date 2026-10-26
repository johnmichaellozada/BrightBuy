<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $imageChoices = [
            'Beauty' => 'images/categories/beauty.png',
            'Electronics' => 'images/categories/electronics.png',
            'Fashion' => 'images/categories/fashion.png',
            'Home & Living' => 'images/categories/home.png',
            'Sports & Outdoors' => 'images/categories/sports.png',
            'Toys' => 'images/categories/toys.png',
        ];

        $builder
            ->add('name')
            ->add('description')
            ->add('image', ChoiceType::class, [
                'choices' => $imageChoices,
                'placeholder' => 'Select a category image',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
