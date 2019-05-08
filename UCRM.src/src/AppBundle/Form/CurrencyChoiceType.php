<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\DataProvider\OrganizationDataProvider;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyChoiceType extends AbstractType
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var OrganizationDataProvider
     */
    private $organizationDataProvider;

    public function __construct(Formatter $formatter, OrganizationDataProvider $organizationDataProvider)
    {
        $this->formatter = $formatter;
        $this->organizationDataProvider = $organizationDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('organization');
        $resolver->setAllowedTypes('organization', [Organization::class, 'null']);

        $resolver->setDefaults(
            [
                'class' => Currency::class,
                'choice_label' => 'currencyLabel',
                'choice_attr' => function (Currency $currency) {
                    return [
                        'data-currency-code' => $currency->getCode(),
                        'data-currency-symbol' => $currency->getSymbol(),
                        'data-currency-fraction-digits' => $currency->getFractionDigits(),
                        'data-currency-format' => $this->formatter->formatCurrency(0, $currency->getCode()),
                    ];
                },
                'prefer_used_currencies' => false,
            ]
        );

        $resolver->setAllowedTypes('prefer_used_currencies', ['bool']);

        $resolver->setDefault(
            'preferred_choices',
            function (Options $options) {
                if (! $options['prefer_used_currencies']) {
                    return [];
                }

                $usedCurrenciesIds = array_map(
                    function (Currency $currency) {
                        return $currency->getId();
                    },
                    $this->organizationDataProvider->getUsedCurrencies()
                );

                return function (Currency $currency) use ($usedCurrenciesIds) {
                    return in_array($currency->getId(), $usedCurrenciesIds, true);
                };
            }
        );

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                $organization = $options['organization'];

                return function (EntityRepository $repository) use ($organization) {
                    $qb = $repository->createQueryBuilder('c');

                    if ($organization instanceof Organization) {
                        $qb
                            ->where('c.obsolete = false OR c.id = :currencyId')
                            ->setParameter('currencyId', $organization->getCurrency()->getId());
                    } else {
                        $qb
                            ->where('c.obsolete = false');
                    }

                    return $qb
                        ->orderBy('c.code', 'ASC')
                        ->setCacheable(true);
                };
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
