<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\WizardFinishingData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WizardFinishingType extends AbstractType
{
    // yes/no values are needed to allow having no choice at the beginning
    public const FEEDBACK_ALLOWED_YES = 'yes';
    public const FEEDBACK_ALLOWED_NO = 'no';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'enableDemoMode',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Enable Demo mode',
            ]
        );

        $builder->add(
            'sendAnonymousStatistics',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Send anonymous statistics and help us improve UCRM',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => WizardFinishingData::class,
            ]
        );
    }
}
