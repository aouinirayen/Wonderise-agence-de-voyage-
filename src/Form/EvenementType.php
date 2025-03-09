<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Guide;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'événement',
                'attr' => [
                    'placeholder' => 'Entrez le nom de l\'événement',
                    'class' => 'form-control'
                ]
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('heure', TimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Heure de l\'événement',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Sélectionnez l\'heure'
                ]
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => [
                    'placeholder' => 'Entrez le lieu de l\'événement',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Décrivez l\'événement',
                    'class' => 'form-control',
                    'rows' => 4
                ]
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de l\'événement',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'data_class' => null,
            ])
            ->add('placeMax', IntegerType::class, [
                'label' => 'Nombre de places maximum',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nombre de places disponibles'
                ]
            ])
            ->add('prix', IntegerType::class, [
                'label' => 'Prix',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le prix'
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'choices' => [
                    'Festival' => 'festival',
                    'Théâtre' => 'theatre',
                    'Cinéma' => 'cinema',
                    'Road Trip' => 'roadtrip',
                    'Camping' => 'camping',
                    'Beach Party' => 'beachparty'
                ],
                'placeholder' => 'Choisir une catégorie',
                'attr' => ['class' => 'form-control']
            ])
            ->add('pays', ChoiceType::class, [
                'choices' => Evenement::getPaysChoices(),
                'placeholder' => 'Choisir un pays',
                'attr' => ['class' => 'form-control']
            ])
            ->add('guide', EntityType::class, [
                'class' => Guide::class,
                'choice_label' => function(Guide $guide) {
                    return $guide->getNom();
                },
                'label' => 'Guide',
                'attr' => ['class' => 'form-control']
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Evenement::getStatusChoices(),
                'label' => 'Statut',
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
