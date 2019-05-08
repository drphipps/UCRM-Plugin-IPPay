<?php

declare(strict_types=1);

/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Entity\IpRange;
use AppBundle\Util\IpRangeParser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IpRangeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->
            add(
                'range',
                TextType::class,
                [
                    'mapped' => false,
                    'trim' => true,
                    'required' => $options['required'],
                    'constraints' => new CustomAssert\IpRange(),
                ]
            );

        $builder->get('range')->addViewTransformer(
            new CallbackTransformer(
                function ($value) {
                    // This exception is needed to prevent error message from NonBlank constraint for invalid values.
                    if ($value && ! IpRangeParser::parse($value)) {
                        throw new TransformationFailedException();
                    }

                    return $value;
                },
                function ($value) {
                    return is_string($value)
                        ? str_replace(html_entity_decode('&#8209;'), '-', $value)
                        : $value;
                }
            )
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                /** @var IpRange $ipRange */
                $ipRange = $event->getData();

                if (! $ipRange) {
                    return;
                }

                $event->getForm()->get('range')->setData(
                    str_replace(html_entity_decode('&#8209;'), '-', $ipRange->getRangeForView())
                );
            }
        );

        // Priority of this listener has to be set to > 0 to make sure it's called before symfony/validator.
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var IpRange $ipRange */
                $ipRange = $event->getData();
                $data = $event->getForm()->get('range')->getData();

                if (! $ipRange || ! $data) {
                    return;
                }

                try {
                    $ipRange->setRangeFromString($data);
                } catch (\InvalidArgumentException $e) {
                    // Ignore. IpRangeConstraint will raise a violation.
                }
            },
            1
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => IpRange::class,
                'error_mapping' => [
                    '.' => 'range',
                ],
            ]
        );
    }
}
