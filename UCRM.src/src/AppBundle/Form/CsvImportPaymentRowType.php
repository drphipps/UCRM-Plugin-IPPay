<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\DataTransformer\DateTimeToStringTransformer;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CsvImportPaymentRowType extends AbstractType
{
    /**
     * @var DateTimeToStringTransformer
     */
    protected $dateTimeToStringTransformer;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->dateTimeToStringTransformer = new DateTimeToStringTransformer(null, null, \DateTime::ATOM);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'client',
            ClientChoiceType::class,
            [
                'required' => false,
                'placeholder' => 'Choose a client',
            ]
        );

        $builder->add(
            'method',
            HiddenType::class,
            [
                'error_bubbling' => false,
            ]
        );
        $builder->add(
            'createdDate',
            HiddenType::class,
            [
                'error_bubbling' => false,
            ]
        );
        $builder->add(
            'amount',
            HiddenType::class,
            [
                'error_bubbling' => false,
            ]
        );
        $builder->add(
            'currency',
            HiddenType::class,
            [
                'error_bubbling' => false,
            ]
        );
        $builder->add(
            'note',
            HiddenType::class,
            [
                'error_bubbling' => false,
            ]
        );

        $builder->get('currency')->addModelTransformer(
            new CallbackTransformer(
                function (?Currency $currency) {
                    return $currency ? $currency->getId() : $currency;
                },
                function ($currencyId) {
                    if ($currencyId) {
                        return $this->entityManager->find(Currency::class, $currencyId);
                    }

                    return $currencyId;
                }
            )
        );

        $builder->get('createdDate')->addModelTransformer($this->dateTimeToStringTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Payment::class,
            ]
        );
    }
}
