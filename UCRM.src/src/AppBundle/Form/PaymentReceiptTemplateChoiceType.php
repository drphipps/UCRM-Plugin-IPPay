<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\PaymentReceiptTemplate;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentReceiptTemplateChoiceType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label' => 'Receipt template',
                'required' => true,
                'class' => PaymentReceiptTemplate::class,
                'choice_label' => function (PaymentReceiptTemplate $paymentReceiptTemplate) {
                    if (! $paymentReceiptTemplate->getIsValid()) {
                        return sprintf(
                            '*%s* %s',
                            $this->translator->trans('invalid'),
                            $paymentReceiptTemplate->getName()
                        );
                    }

                    return $paymentReceiptTemplate->getName();
                },
                'selectedPaymentReceiptTemplate' => null,
            ]
        );

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                $selectedPaymentReceiptTemplate = $options['selectedPaymentReceiptTemplate'];

                return function (EntityRepository $er) use ($selectedPaymentReceiptTemplate) {
                    $qb = $er->createQueryBuilder('prt')
                        ->andWhere('prt.deletedAt IS NULL')
                        ->andWhere('prt.isValid = TRUE')
                        ->addOrderBy('prt.name', 'ASC');

                    if ($selectedPaymentReceiptTemplate) {
                        $qb->orWhere('prt.id = :paymentReceiptTemplate')
                            ->setParameter('paymentReceiptTemplate', $selectedPaymentReceiptTemplate);
                    }

                    return $qb;
                };
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
