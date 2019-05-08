<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\CsvMappingData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class CsvImportClientType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'mapping',
            CsvImportClientMappingType::class,
            [
                'mapping_choices' => $options['mapping_choices'],
            ]
        );

        if ($options['include_organization_select']) {
            $builder->add(
                'organization',
                OrganizationChoiceType::class,
                [
                    'required' => true,
                    'label' => 'Import to organization',
                    'constraints' => new NotNull(),
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => CsvMappingData::class,
                'mapping_choices' => [],
                'include_organization_select' => true,
            ]
        );

        $resolver->setAllowedTypes('mapping_choices', 'array');
        $resolver->setAllowedTypes('include_organization_select', 'bool');
    }
}
