<?php

namespace App\Form;

use App\Entity\Country;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CountryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('img', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file',
                    ])
                ],
            ])
            ->add('description', TextType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => ['class' => 'form-control']
            ])
            ->add('currency', TextType::class, [
                'required' => false,
                'label' => 'Currency',
                'attr' => ['class' => 'form-control']
            ])
            ->add('isoCode', TextType::class, [
                'required' => false,
                'label' => 'ISO Code',
                'attr' => ['class' => 'form-control']
            ])
            ->add('callingCode', TextType::class, [
                'required' => false,
                'label' => 'Calling Code',
                'attr' => ['class' => 'form-control']
            ])
            ->add('climate', TextType::class, [
                'required' => false,
                'label' => 'Climate',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Country::class,
        ]);
    }
}