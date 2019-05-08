<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Financial\InvoiceTemplate;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class InvoiceTemplateChoiceType extends AbstractType
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
                'label' => 'Invoice template',
                'required' => true,
                'class' => InvoiceTemplate::class,
                'choice_label' => function (InvoiceTemplate $invoiceTemplate) {
                    if (! $invoiceTemplate->getIsValid()) {
                        return sprintf(
                            '*%s* %s',
                            $this->translator->trans('invalid'),
                            $invoiceTemplate->getName()
                        );
                    }

                    return $invoiceTemplate->getName();
                },
                'selectedInvoiceTemplate' => null,
            ]
        );

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                $selectedInvoiceTemplate = $options['selectedInvoiceTemplate'];

                return function (EntityRepository $er) use ($selectedInvoiceTemplate) {
                    $qb = $er->createQueryBuilder('it')
                        ->andWhere('it.deletedAt IS NULL')
                        ->andWhere('it.isValid = TRUE')
                        ->addOrderBy('it.name', 'ASC');

                    if ($selectedInvoiceTemplate) {
                        $qb->orWhere('it.id = :invoiceTemplate')
                            ->setParameter('invoiceTemplate', $selectedInvoiceTemplate);
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
