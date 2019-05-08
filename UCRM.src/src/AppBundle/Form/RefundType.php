<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Client;
use AppBundle\Entity\Refund;
use AppBundle\Form\Data\ClientChoiceData;
use AppBundle\Util\Formatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RefundType extends AbstractType
{
    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'method',
            ChoiceType::class,
            [
                'placeholder' => 'Make a choice.',
                'choices' => array_flip(Refund::METHOD_TYPE),
            ]
        );

        $builder->add(
            'createdDate',
            DateTimeType::class,
            [
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
            ]
        );

        $builder->add(
            'amount',
            FloatType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'note',
            TextareaType::class,
            [
                'required' => false,
            ]
        );

        $this->addClientField($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Refund::class,
                'client' => null,
            ]
        );

        $resolver->setAllowedTypes('client', [Client::class, 'null']);
    }

    public function addClientField(FormBuilderInterface $builder, array $options): void
    {
        $clientOptions = [];
        $clientOptions['required'] = true;
        $clientOptions['only_clients_with_credit'] = true;
        $clientOptions['choice_label'] = function (ClientChoiceData $client) {
            $accountStandingsCredit = $this->formatter->formatCurrency(
                $client->accountStandingsRefundableCredit,
                $client->currencyCode,
                $client->locale
            );

            return sprintf(
                '%s (%s)',
                $client->formatName(),
                $accountStandingsCredit
            );
        };
        if ($options['client']) {
            // Disables loading of client choices when client is already chosen and the field is hidden.
            $clientOptions['choices'] = [ClientChoiceData::fromClient($options['client'])];
        }

        $builder->add(
            'client',
            ClientChoiceType::class,
            $clientOptions
        );
    }
}
