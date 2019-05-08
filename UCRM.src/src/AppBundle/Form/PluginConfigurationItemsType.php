<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Service\Plugin\PluginManifestConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PluginConfigurationItemsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PluginManifestConfiguration $item */
        foreach ($options['configuration_items'] as $item) {
            $builderOptions = [
                'label' => $item->label,
                'constraints' => $item->required
                    ? [
                        new NotBlank(),
                    ]
                    : [],
                'required' => $item->required,
                'translation_domain' => false,
            ];

            if ($item->type === ChoiceType::class) {
                if (! $item->required) {
                    $builderOptions['placeholder'] = 'Make a choice.';
                }
                $builderOptions['choices'] = $item->choices;
            } elseif (in_array($item->type, [DateTimeType::class, DateType::class], true)) {
                $builderOptions['widget'] = 'single_text';
                $builderOptions['html5'] = false;
            } elseif ($item->type === FileType::class && in_array($item->key, $options['existingFiles'], true)) {
                $builderOptions['required'] = false;
                $builderOptions['constraints'] = [];
            }

            $builder->add(
                $item->key,
                $item->type,
                $builderOptions
            );
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (FormEvent $event) {
                $data = $event->getData();

                foreach ($event->getForm()->all() as $key => $field) {
                    if (! array_key_exists($key, $data)) {
                        continue;
                    }

                    $fieldData = $data[$key];

                    try {
                        foreach ($field->getConfig()->getViewTransformers() as $transformer) {
                            $transformer->transform($fieldData);
                        }
                    } catch (TransformationFailedException $exception) {
                        $data[$key] = null;
                        $field->addError(
                            new FormError(
                                'Unable to use saved value for property "{{ property }}". Please set the value again. Saved value was "{{ value }}".',
                                'Unable to use saved value for property "{{ property }}". Please set the value again. Saved value was "{{ value }}".',
                                [
                                    '{{ property }}' => $field->getConfig()->getOption('label'),
                                    '{{ value }}' => $fieldData,
                                ]
                            )
                        );
                    }
                }

                $event->setData($data);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => null,
                'configuration_items' => [],
                'existingFiles' => [],
            ]
        );

        $resolver->setAllowedTypes('configuration_items', 'array');
        $resolver->setAllowedTypes('existingFiles', 'array');
    }
}
