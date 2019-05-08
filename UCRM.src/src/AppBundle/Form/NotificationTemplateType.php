<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\NotificationTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($builder) {
                /** @var NotificationTemplate|null $template */
                $template = $event->getData();
                $isSuspend = $template
                    && in_array($template->getType(), NotificationTemplate::SUSPENSION_TYPES, true);

                $event->getForm()->add(
                    $builder->getFormFactory()->createNamed(
                        'subject',
                        TextType::class,
                        null,
                        [
                            'label' => $isSuspend ? 'Heading' : 'Subject',
                            'auto_initialize' => false,
                        ]
                    )
                );

                $event->getForm()->add(
                    $builder->getFormFactory()->createNamed(
                        'body',
                        TextareaType::class,
                        null,
                        [
                            'label' => $isSuspend ? 'Template' : 'Mail body',
                            'attr' => [
                                'rows' => 8,
                            ],
                            'auto_initialize' => false,
                        ]
                    )
                );
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => NotificationTemplate::class,
            ]
        );
    }
}
