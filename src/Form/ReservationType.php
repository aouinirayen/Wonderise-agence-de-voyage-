<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-zÀ-ÿ\s-]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, des espaces et des tirets'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom',
                    'pattern' => '^[A-Za-zÀ-ÿ\s-]{2,50}$',
                    'title' => 'Le nom doit contenir entre 2 et 50 caractères (lettres, espaces et tirets uniquement)'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le prénom est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-zÀ-ÿ\s-]+$/',
                        'message' => 'Le prénom ne peut contenir que des lettres, des espaces et des tirets'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom',
                    'pattern' => '^[A-Za-zÀ-ÿ\s-]{2,50}$',
                    'title' => 'Le prénom doit contenir entre 2 et 50 caractères (lettres, espaces et tirets uniquement)'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'email est obligatoire'
                    ]),
                    new Assert\Email([
                        'message' => 'L\'email "{{ value }}" n\'est pas valide'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@email.com',
                    'type' => 'email'
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '06 12 34 56 78'
                ]
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La ville est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'La ville doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La ville ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-zÀ-ÿ\s-]+$/',
                        'message' => 'La ville ne peut contenir que des lettres, des espaces et des tirets'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre ville',
                    'pattern' => '^[A-Za-zÀ-ÿ\s-]{2,50}$',
                    'title' => 'La ville doit contenir entre 2 et 50 caractères (lettres, espaces et tirets uniquement)'
                ]
            ])
            ->add('nombrePersonne', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nombre de personnes est obligatoire'
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Le nombre de personnes doit être au moins {{ compared_value }}'
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => $options['places_disponibles'] ?? 999,
                        'message' => 'Le nombre de personnes ne peut pas dépasser {{ compared_value }}'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => $options['places_disponibles'] ?? 999,
                    'placeholder' => 'Nombre de personnes',
                    'title' => 'Le nombre de personnes doit être compris entre 1 et le nombre de places disponibles'
                ]
            ])
            ->add('mode_paiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'required' => true,
                'choices' => [
                    'Carte bancaire' => 'carte',
                    'PayPal' => 'paypal',
                    'Virement bancaire' => 'virement'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ajoutez un commentaire si nécessaire...',
                    'rows' => 3,
                    'maxlength' => 1000
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'places_disponibles' => null,
        ]);
    }
}
