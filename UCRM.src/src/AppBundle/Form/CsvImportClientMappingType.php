<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Component\Import\Annotation\CsvColumn;
use AppBundle\Component\Import\DataProvider\CsvColumnDataProvider;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ServiceImportItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CsvImportClientMappingType extends AbstractType
{
    /**
     * @var CsvColumnDataProvider
     */
    private $csvColumnDataProvider;

    public function __construct(CsvColumnDataProvider $csvColumnDataProvider)
    {
        $this->csvColumnDataProvider = $csvColumnDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // keys of $options['mapping_choices'] MUST be kept
        $choices = array_merge(
            ['Choose column...' => ''],
            array_flip($options['mapping_choices'])
        );

        /** @var CsvColumn[] $csvColumns */
        $csvColumns = array_merge(
            $this->csvColumnDataProvider->getCsvColumns(new \ReflectionClass(ClientImportItem::class)),
            $this->csvColumnDataProvider->getCsvColumns(new \ReflectionClass(ServiceImportItem::class))
        );

        foreach ($csvColumns as $csvColumn) {
            $builder->add(
                $csvColumn->csvMappingField,
                ChoiceType::class,
                [
                    'choices' => $choices,
                    'choice_translation_domain' => false,
                    'required' => false,
                    'label' => $csvColumn->label,
                    'attr' => $csvColumn->description
                        ? [
                            'data-help' => $csvColumn->description,
                        ]
                        : [],
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
                'mapping_choices' => [],
            ]
        );

        $resolver->setAllowedTypes('mapping_choices', 'array');
    }
}
