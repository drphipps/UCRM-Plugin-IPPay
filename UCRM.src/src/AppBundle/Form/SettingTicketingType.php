<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\TicketingData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingTicketingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'ticketingEnabled',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::TICKETING_ENABLED],
                'required' => false,
            ]
        );

        $builder->add(
            'attachmentImportLimit',
            NumberType::class,
            [
                'label' => Option::NAMES[Option::TICKETING_IMAP_ATTACHMENT_FILESIZE_IMPORT_LIMIT],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TicketingData::class,
            ]
        );
    }
}
