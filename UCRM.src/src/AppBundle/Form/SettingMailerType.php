<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\MailerData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingMailerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'mailerTransport',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::MAILER_TRANSPORT],
                'choices' => array_flip(Option::MAILER_TRANSPORTS),
                'choice_translation_domain' => false,
                'expanded' => true,
            ]
        );

        $builder->add(
            'mailerUsername',
            TextType::class,
            [
                'label' => Option::NAMES[Option::MAILER_USERNAME],
                'required' => false,
            ]
        );

        $passwordPlaceholder = $builder->getData() && $builder->getData()->mailerPassword
            ? [
                'placeholder' => 'password must be provided again when changing settings',
            ]
            : [];
        $builder->add(
            'mailerPassword',
            PasswordType::class,
            [
                'label' => Option::NAMES[Option::MAILER_PASSWORD],
                'required' => false,
                'attr' => array_merge($passwordPlaceholder, ['autocomplete' => 'new-password']),
                'data' => null,
            ]
        );

        $builder->add(
            'mailerHost',
            TextType::class,
            [
                'label' => Option::NAMES[Option::MAILER_HOST],
                'required' => false,
            ]
        );

        $builder->add(
            'mailerPort',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::MAILER_PORT],
                'required' => false,
            ]
        );

        $builder->add(
            'mailerEncryption',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::MAILER_ENCRYPTION],
                'required' => false,
                'choices' => array_flip(Option::MAILER_ENCRYPTIONS),
                'choice_translation_domain' => false,
                'expanded' => true,
            ]
        );

        $builder->add(
            'mailerAuthMode',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::MAILER_AUTH_MODE],
                'required' => false,
                'choices' => array_flip(Option::MAILER_AUTH_MODS),
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'verifySslCertificate',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::MAILER_VERIFY_SSL_CERTIFICATES],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => MailerData::class,
            ]
        );
    }
}
