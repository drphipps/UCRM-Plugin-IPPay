<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\TicketingNotificationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class SettingTicketingNotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'automaticReplyEnabled',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::TICKETING_IMAP_AUTOMATIC_REPLY_ENABLED],
                'required' => false,
            ]
        );

        $builder->add(
            'automaticReplyNotificationTemplate',
            NotificationTemplateType::class,
            [
                'constraints' => new Valid(),
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TicketingNotificationData::class,
            ]
        );
    }
}
