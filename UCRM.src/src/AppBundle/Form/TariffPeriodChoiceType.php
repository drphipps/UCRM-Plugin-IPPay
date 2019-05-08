<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Twig\Extension;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TariffPeriodChoiceType extends AbstractType
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Extension
     */
    private $extension;

    public function __construct(Formatter $formatter, Extension $extension)
    {
        $this->formatter = $formatter;
        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('service');
        $resolver->setAllowedTypes('service', Service::class);

        $resolver->setDefaults(
            [
                'class' => TariffPeriod::class,
                'label' => 'Service plan period',
                'choice_label' => function (TariffPeriod $tariffPeriod) {
                    $price = $this->formatter->formatCurrency(
                        $tariffPeriod->getPrice(),
                        $tariffPeriod->getTariff()->getOrganization()->getCurrency()->getCode(),
                        $tariffPeriod->getTariff()->getOrganization()->getLocale()
                    );

                    $period = $this->extension->tariffPricePeriod(
                        $tariffPeriod->getPeriod()
                    );

                    return sprintf('%s / %s', $price, $period);
                },
                'choice_attr' => function (TariffPeriod $tariffPeriod) {
                    return [
                        'data-tariff-period' => $tariffPeriod->getPeriod(),
                    ];
                },
                'tariff' => null,
            ]
        );

        $resolver->setAllowedTypes('tariff', [Tariff::class, 'null']);

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                /** @var Service $service */
                $service = $options['service'];

                return function (EntityRepository $repository) use ($service, $options) {
                    $qb = $repository->createQueryBuilder('tp')
                        ->innerJoin('tp.tariff', 't')
                        ->where('tp.enabled = TRUE')
                        ->orWhere('tp = :servicePeriod')
                        ->andWhere('t = :tariff')
                        ->andWhere('t.organization = :organization')
                        ->setParameter('organization', $service->getClient()->getOrganization())
                        ->setParameter('tariff', $options['tariff'])
                        ->setParameter('servicePeriod', $service->getTariffPeriod());

                    return $qb->orderBy('tp.period', 'ASC');
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
