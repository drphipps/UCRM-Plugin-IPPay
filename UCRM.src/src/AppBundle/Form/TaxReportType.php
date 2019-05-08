<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class TaxReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'from',
                DateType::class,
                [
                    'required' => true,
                    'widget' => 'single_text',
                    'html5' => false,
                ]
            )
            ->add(
                'to',
                DateType::class,
                [
                    'required' => true,
                    'widget' => 'single_text',
                    'html5' => false,
                ]
            )
            ->add('organization', OrganizationChoiceType::class);
    }
}
