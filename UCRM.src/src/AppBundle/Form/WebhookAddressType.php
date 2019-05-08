<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\WebhookAddress;
use AppBundle\Entity\WebhookEventType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebhookAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'url',
            UrlType::class,
            [
                'required' => true,
                'label' => 'URL',
            ]
        );

        $builder->add(
            'isActive',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'anyEvent',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'verifySslCertificate',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'webhookEventTypes',
            EntityType::class,
            [
                'multiple' => true,
                'class' => WebhookEventType::class,
                'choice_label' => 'eventName',
                'required' => false,
                'query_builder' => function (EntityRepository $repository) {
                    return $repository
                        ->createQueryBuilder('wae')
                        ->orderBy('wae.eventName', 'ASC');
                },
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => WebhookAddress::class,
            ]
        );
    }
}
