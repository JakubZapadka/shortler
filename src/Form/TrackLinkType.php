<?php

namespace App\Form;

use App\Entity\Links;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TrackLinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ourUrl', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\NotNull(),
                    new Assert\Url([
                        'protocols' => ['https'],
                    ]),
                ],
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter your short link here'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Links::class,
        ]);
    }
}
