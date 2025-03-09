<?php

namespace App\Form;

use App\Entity\Guide;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class GuideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'attr' => ['placeholder' => 'Votre nom']
            ])
            ->add('prenom', null, [
                'attr' => ['placeholder' => 'Votre prénom']
            ])
            ->add('email', null, [
                'attr' => ['placeholder' => 'Votre email']
            ])
            ->add('numTelephone', null, [
                'attr' => ['placeholder' => 'Votre numéro de téléphone']
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre expérience et vos spécialités en tant que guide...'
                ]
            ])
            ->add('facebook', null, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre profil Facebook'
                ]
            ])
            ->add('instagram', null, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre profil Instagram'
                ]
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG ou PNG)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Guide::class,
        ]);
    }
}
