<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class JobDurationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(
            new CallbackTransformer(
                function (?int $duration): array {
                    if ($duration === null) {
                        $hours = 0;
                        $minutes = 0;
                    } else {
                        $hours = (int) ($duration / 60);
                        $minutes = $duration % 60;
                        $minutes = $minutes - ($minutes % 5);
                    }

                    return [
                        'hours' => $hours,
                        'minutes' => $minutes,
                    ];
                },
                function (array $duration): int {
                    return $duration['hours'] * 60 + $duration['minutes'];
                }
            )
        );

        $builder->add(
            'hours',
            ChoiceType::class,
            [
                'choices' => array_combine(range(0, 23), range(0, 23)),
                'choice_translation_domain' => false,
                'choice_label' => function ($choiceValue, $key) use ($options) {
                    if (! $options['choices_with_units']) {
                        return $key;
                    }

                    return $this->translator->transChoice(
                        '%time% hrs',
                        (int) $key,
                        [
                            '%time%' => $key,
                        ]
                    );
                },
            ]
        );

        $builder->add(
            'minutes',
            ChoiceType::class,
            [
                'choices' => array_combine(range(0, 55, 5), range(0, 55, 5)),
                'choice_translation_domain' => false,
                'choice_label' => function ($choiceValue, $key) use ($options) {
                    if (! $options['choices_with_units']) {
                        return $key;
                    }

                    return $this->translator->transChoice(
                        '%time% min',
                        (int) $key,
                        [
                            '%time%' => $key,
                        ]
                    );
                },
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices_with_units' => false,
            ]
        );

        $resolver->setAllowedTypes('choices_with_units', 'bool');
    }
}
