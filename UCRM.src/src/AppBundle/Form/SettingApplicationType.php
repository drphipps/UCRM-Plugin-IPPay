<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\ApplicationData;
use Nette\Utils\Strings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'siteName',
            TextType::class,
            [
                'label' => Option::NAMES[Option::SITE_NAME],
                'required' => false,
            ]
        );

        $builder->add(
            'serverIp',
            TextType::class,
            [
                'label' => Option::NAMES[Option::SERVER_IP],
                'required' => false,
            ]
        );

        $builder->add(
            'serverPort',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::SERVER_PORT],
            ]
        );

        $builder->add(
            'serverSuspendPort',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::SERVER_SUSPEND_PORT],
            ]
        );

        $builder->add(
            'serverFqdn',
            TextType::class,
            [
                'label' => Option::NAMES[Option::SERVER_FQDN],
                'required' => false,
            ]
        );
        $builder->get('serverFqdn')->addModelTransformer(
            new CallbackTransformer(
                function (?string $value) {
                    return $value === null ? null : Strings::lower($value);
                },
                function (?string $value) {
                    return $value === null ? null : Strings::lower($value);
                }
            )
        );

        $builder->add(
            'mapboxToken',
            TextType::class,
            [
                'label' => Option::NAMES[Option::MAPBOX_TOKEN],
                'required' => false,
            ]
        );

        $builder->add(
            'googleApiKey',
            TextType::class,
            [
                'label' => Option::NAMES[Option::GOOGLE_API_KEY],
                'required' => false,
            ]
        );

        $builder->add(
            'exportPageSize',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::PDF_PAGE_SIZE_EXPORT],
                'choices' => array_flip(Pdf::PAGE_SIZES),
            ]
        );

        $builder->add(
            'invoicePageSize',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::PDF_PAGE_SIZE_INVOICE],
                'choices' => array_flip(Pdf::PAGE_SIZES),
            ]
        );

        $builder->add(
            'paymentReceiptPageSize',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::PDF_PAGE_SIZE_PAYMENT_RECEIPT],
                'choices' => array_flip(Pdf::PAGE_SIZES),
            ]
        );

        $builder->add(
            'errorReporting',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::ERROR_REPORTING],
                'required' => false,
            ]
        );

        $builder->add(
            'sendAnonymousStatistics',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SEND_ANONYMOUS_STATISTICS],
                'required' => false,
            ]
        );

        $builder->add(
            'clientIdType',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::CLIENT_ID_TYPE],
                'choices' => array_flip(Option::CLIENT_ID_TYPES),
            ]
        );

        $builder->add(
            'balanceStyle',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::BALANCE_STYLE],
                'choices' => array_flip(Option::BALANCE_STYLES),
            ]
        );

        $builder->add(
            'clientIdNext',
            IntegerType::class,
            [
                'label' => 'Next client ID',
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ApplicationData::class,
            ]
        );
    }
}
