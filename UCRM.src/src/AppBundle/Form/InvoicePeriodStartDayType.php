<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Util\Formatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class InvoicePeriodStartDayType extends AbstractType
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Formatter $formatter, TranslatorInterface $translator)
    {
        $this->formatter = $formatter;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('invoice_period_start_day_choices');

        $resolver->setDefaults(
            [
                'choices' => function (Options $options) {
                    return $this->getInvoicingStartDayChoices($options['invoice_period_start_day_choices']);
                },
                'choice_translation_domain' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    private function getInvoicingStartDayChoices(array $choices): array
    {
        $choices = array_map(
            function ($value) {
                if (is_numeric($value)) {
                    return $this->formatter->formatNumber($value, 'ordinal');
                }

                return $this->translator->trans($value);
            },
            $choices
        );

        return array_flip($choices);
    }
}
