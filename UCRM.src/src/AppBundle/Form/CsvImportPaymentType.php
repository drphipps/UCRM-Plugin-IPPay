<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Component\Import\CustomCsvImport;
use AppBundle\DataProvider\CurrencyDataProvider;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Payment;
use Nette\Utils\Strings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class CsvImportPaymentType extends AbstractType
{
    public const PREFIX_PAYMENT_METHOD = 'paymentMethod/';
    public const PREFIX_CURRENCY = 'currency/';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CustomAttributeDataProvider
     */
    private $customAttributeDataProvider;

    /**
     * @var CurrencyDataProvider
     */
    private $currencyDataProvider;

    public function __construct(
        TranslatorInterface $translator,
        CustomAttributeDataProvider $customAttributeDataProvider,
        CurrencyDataProvider $currencyDataProvider
    ) {
        $this->translator = $translator;
        $this->customAttributeDataProvider = $customAttributeDataProvider;
        $this->currencyDataProvider = $currencyDataProvider;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $labels = CustomCsvImport::LABELS_PAYMENTS;
        $choices = array_flip($options['mapChoices']);

        $builder->add(
            'client',
            ChoiceType::class,
            [
                'choices' => $choices,
                'choice_translation_domain' => false,
                'required' => false,
                'label' => $labels['client'],
            ]
        );

        $clientMatchChoices = [
            $this->translator->trans('Client ID') => CustomCsvImport::CLIENT_MATCH_BY_ID,
            $this->translator->trans('Custom ID') => CustomCsvImport::CLIENT_MATCH_BY_CUSTOM_ID,
        ];
        $customAttributes = $this->customAttributeDataProvider->getByAttributeType(
            CustomAttribute::ATTRIBUTE_TYPE_CLIENT
        );
        foreach ($customAttributes as $attribute) {
            $clientMatchChoices[$attribute->getName()] = $attribute->getKey();
        }

        $builder->add(
            'clientMatch',
            ChoiceType::class,
            [
                'choices' => $clientMatchChoices,
                'choice_translation_domain' => false,
                'required' => true,
                'label' => $labels['client'],
                'preferred_choices' => [
                    CustomCsvImport::CLIENT_MATCH_BY_ID,
                    CustomCsvImport::CLIENT_MATCH_BY_CUSTOM_ID,
                ],
            ]
        );

        $builder->add(
            'amount',
            ChoiceType::class,
            [
                'choices' => $choices,
                'choice_translation_domain' => false,
                'required' => true,
                'label' => $labels['amount'],
            ]
        );

        $currencies = [];
        foreach ($this->currencyDataProvider->getAllCurrenciesCsvMapping() as $key => $currency) {
            $currencies[$currency->getCurrencyLabel()] = self::PREFIX_CURRENCY . $key;
        }

        $builder->add(
            'currency',
            ChoiceType::class,
            [
                'choices' => $choices + $currencies,
                'choice_translation_domain' => false,
                'required' => true,
                'label' => $labels['currency'],
                'group_by' => function (string $value, string $key) {
                    if (Strings::startsWith($value, self::PREFIX_CURRENCY)) {
                        return $this->translator->trans('Currency');
                    }
                    if ($value === '') {
                        return null;
                    }

                    return $this->translator->trans('CSV columns');
                },
            ]
        );

        $paymentMethods = [];
        foreach (Payment::METHOD_TYPE as $key => $method) {
            $paymentMethods[$this->translator->trans($method)] = self::PREFIX_PAYMENT_METHOD . $key;
        }

        $builder->add(
            'method',
            ChoiceType::class,
            [
                'choices' => $choices + $paymentMethods,
                'choice_translation_domain' => false,
                'required' => true,
                'label' => $labels['method'],
                'group_by' => function (string $value, string $key) {
                    if (Strings::startsWith($value, self::PREFIX_PAYMENT_METHOD)) {
                        return $this->translator->trans('Payment method');
                    }
                    if ($value === '') {
                        return null;
                    }

                    return $this->translator->trans('CSV columns');
                },
            ]
        );

        $builder->add(
            'createdDate',
            ChoiceType::class,
            [
                'choices' => $choices,
                'choice_translation_domain' => false,
                'required' => false,
                'label' => $labels['createdDate'],
            ]
        );

        $builder->add(
            'note',
            ChoiceType::class,
            [
                'choices' => $choices,
                'choice_translation_domain' => false,
                'required' => false,
                'label' => $labels['note'],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'mapChoices' => [],
            ]
        );

        $resolver->setAllowedTypes('mapChoices', 'array');
    }
}
