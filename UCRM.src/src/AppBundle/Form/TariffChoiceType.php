<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TariffChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('service');
        $resolver->setAllowedTypes('service', Service::class);

        $resolver->setDefaults(
            [
                'class' => Tariff::class,
                'label' => 'Service plan',
                'choice_label' => 'name',
                'placeholder' => 'Choose a service plan',
                'periodChangeDisabled' => false,
                'choice_attr' => function (Tariff $tariff) {
                    return [
                        'data-taxable' => (int) $tariff->getTaxable(),
                        'data-tax' => $tariff->getTax() ? $tariff->getTax()->getId() : null,
                        'data-invoice-label' => $tariff->getInvoiceLabelOrName(),
                        'data-minimum-contract-length' => $tariff->getMinimumContractLengthMonths(),
                        'data-setup-fee' => $tariff->getSetupFee(),
                        'data-early-termination-fee' => $tariff->getEarlyTerminationFee(),
                    ];
                },
            ]
        );

        $resolver->setAllowedTypes('periodChangeDisabled', 'boolean');

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                /** @var Service $service */
                $service = $options['service'];

                /** @var bool $periodChangeDisabled */
                $periodChangeDisabled = $options['periodChangeDisabled'];

                return function (EntityRepository $repository) use ($service, $periodChangeDisabled) {
                    $organization = $service->getClient()->getOrganization();
                    $tariff = $service->getTariff();
                    $qb = $repository->createQueryBuilder('t');

                    if ($tariff) {
                        $qb->andWhere('(t.deletedAt IS NULL AND t.organization = :organization) OR t.id = :tariffId');
                        $qb->setParameter('tariffId', $tariff->getId());
                        $with = 'tp.tariff = t.id AND (tp.enabled = TRUE OR t.id = :tariffId)';
                        if ($periodChangeDisabled) {
                            $qb->setParameter('period', $service->getTariffPeriod()->getPeriod());
                            $with .= ' AND tp.period = :period';
                        }
                    } else {
                        $qb->andWhere('t.deletedAt IS NULL');
                        $qb->andWhere('t.organization = :organization');
                        $with = 'tp.tariff = t.id AND tp.enabled = TRUE';
                    }

                    return $qb
                        ->setParameter('organization', $organization)
                        ->innerJoin(TariffPeriod::class, 'tp', Join::WITH, $with)
                        ->orderBy('t.name', 'ASC');
                };
            }
        );
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
