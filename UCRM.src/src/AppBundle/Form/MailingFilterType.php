<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientTag;
use AppBundle\Entity\Device;
use AppBundle\Entity\Service;
use AppBundle\Entity\Site;
use AppBundle\Entity\Tariff;
use AppBundle\Form\Data\MailingFilterData;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailingFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'filterOrganizations',
            OrganizationChoiceType::class,
            [
                'label' => 'Organization',
                'multiple' => true,
                'required' => false,
            ]
        );

        $builder->add(
            'filterClientTypes',
            ChoiceType::class,
            [
                'label' => 'Client type',
                'choices' => [
                    'Residential' => Client::TYPE_RESIDENTIAL,
                    'Company' => Client::TYPE_COMPANY,
                ],
                'multiple' => true,
                'required' => false,
            ]
        );

        $builder->add(
            'filterIncludeLeads',
            ChoiceType::class,
            [
                'label' => 'Client leads',
                'choices' => [
                    'Only client leads' => true,
                    'Only active clients' => false,
                ],
                'placeholder' => 'Make a choice.',
                'required' => false,
            ]
        );

        $builder->add(
            'filterClientTags',
            EntityType::class,
            [
                'label' => 'Client tag',
                'class' => ClientTag::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('c')
                        ->orderBy('c.name');
                },
                'multiple' => true,
                'required' => false,
            ]
        );

        $builder->add(
            'filterServicePlans',
            EntityType::class,
            [
                'label' => 'Service plan',
                'class' => Tariff::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('t')
                        ->where('t.deletedAt IS NULL')
                        ->orderBy('t.name');
                },
                'multiple' => true,
                'required' => false,
            ]
        );

        $builder->add(
            'filterPeriodStartDays',
            InvoicePeriodStartDayType::class,
            [
                'label' => 'Period start day',
                'invoice_period_start_day_choices' => Service::INVOICING_PERIOD_START_DAY,
                'multiple' => true,
                'required' => false,
            ]
        );

        $builder->add(
            'filterSites',
            EntityType::class,
            [
                'label' => 'Site',
                'class' => Site::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('s')
                        ->where('s.deletedAt IS NULL')
                        ->orderBy('s.name');
                },
                'multiple' => true,
                'required' => false,
            ]
        );

        $builder->add(
            'filterDevices',
            DeviceChoiceType::class,
            [
                'label' => 'Device',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('d')
                        ->where('d.deletedAt IS NULL')
                        ->orderBy('d.name');
                },
                'multiple' => true,
                'required' => false,
                'group_by' => function (Device $device) {
                    return $device->getSite()->getName();
                },
                'placeholder' => '',
                'attr' => [
                    'placeholder' => '',
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => MailingFilterData::class,
            ]
        );
    }
}
