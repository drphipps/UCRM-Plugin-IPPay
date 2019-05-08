<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\DataProvider\ServiceEndDateDataProvider;
use AppBundle\DataTransformer\DateTimeToStringTransformer;
use AppBundle\Entity\Service;
use AppBundle\Form\Data\ServiceReactivateData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceReactivateType extends AbstractType
{
    /**
     * @var ServiceEndDateDataProvider
     */
    private $endDateDataProvider;
    /**
     * @var DateTimeToStringTransformer
     */
    private $dateTimeToStringTransformer;

    public function __construct(
        ServiceEndDateDataProvider $endDateDataProvider,
        DateTimeToStringTransformer $dateTimeToStringTransformer
    ) {
        $this->endDateDataProvider = $endDateDataProvider;
        $this->dateTimeToStringTransformer = $dateTimeToStringTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'endDate',
            ChoiceType::class,
            [
                'choices' => $this->endDateDataProvider->getAvailableEndDates($options['service']),
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => 'No end date',
                'label' => 'New end date',
            ]
        );
        $builder->get('endDate')->addModelTransformer($this->dateTimeToStringTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ServiceReactivateData::class,
                'csrf_protection' => false,
            ]
        );

        $resolver->setRequired('service');
        $resolver->setAllowedTypes('service', Service::class);
    }
}
