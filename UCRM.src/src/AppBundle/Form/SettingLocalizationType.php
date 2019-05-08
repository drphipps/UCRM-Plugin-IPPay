<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\LocalizationData;
use AppBundle\Util\DateTimeFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingLocalizationType extends AbstractType
{
    /**
     * @var DateTimeFormatter
     */
    private $dateTimeFormatter;

    public function __construct(DateTimeFormatter $dateTimeFormatter)
    {
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'appLocale',
            LocaleChoiceType::class,
            [
                'label' => Option::NAMES[Option::APP_LOCALE],
            ]
        );
        $builder->add(
            'appTimezone',
            TimezoneChoiceType::class,
            [
                'label' => Option::NAMES[Option::APP_TIMEZONE],
            ]
        );

        $builder->add(
            'formatDateDefault',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::FORMAT_DATE_DEFAULT],
                'choices' => $this->formatOptions(
                    DateTimeFormatter::FORMAT_DATE_OPTIONS,
                    new \DateTime('2017-01-09'),
                    [$this->dateTimeFormatter, 'formatDate']
                ),
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'formatDateAlternative',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::FORMAT_DATE_ALTERNATIVE],
                'choices' => $this->formatOptions(
                    DateTimeFormatter::FORMAT_DATE_OPTIONS,
                    new \DateTime('2017-01-09'),
                    [$this->dateTimeFormatter, 'formatDate']
                ),
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'formatTime',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::FORMAT_TIME],
                'choices' => $this->formatOptions(
                    DateTimeFormatter::FORMAT_TIME_OPTIONS,
                    new \DateTime('2017-01-09 13:38'),
                    [$this->dateTimeFormatter, 'formatTime']
                ),
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'formatUseDefaultDecimalSeparator',
            CheckboxType::class,
            [
                'label' => 'use language default',
                'required' => false,
            ]
        );

        $builder->add(
            'formatDecimalSeparator',
            TextType::class,
            [
                'label' => Option::NAMES[Option::FORMAT_DECIMAL_SEPARATOR],
                'required' => false,
                'trim' => false,
            ]
        );

        $builder->add(
            'formatUseDefaultThousandsSeparator',
            CheckboxType::class,
            [
                'label' => 'use language default',
                'required' => false,
            ]
        );

        $builder->add(
            'formatThousandsSeparator',
            TextType::class,
            [
                'label' => Option::NAMES[Option::FORMAT_THOUSANDS_SEPARATOR],
                'required' => false,
                'trim' => false,
            ]
        );
    }

    private function formatOptions(array $options, \DateTimeInterface $date, callable $formatter): array
    {
        $formatter = \Closure::fromCallable($formatter);

        array_walk(
            $options,
            function (&$label, $format) use ($date, $formatter) {
                $label = sprintf(
                    '%s (%s)',
                    $label,
                    $formatter($date, $format)
                );
            }
        );

        return array_flip($options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => LocalizationData::class,
            ]
        );
    }
}
