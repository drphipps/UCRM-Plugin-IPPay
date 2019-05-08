<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class ProformaInvoiceTemplateChoiceType extends AbstractType
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
                'label' => 'Proforma invoice template',
                'required' => true,
                'class' => ProformaInvoiceTemplate::class,
                'choice_label' => function (ProformaInvoiceTemplate $proformaInvoiceTemplate) {
                    if (! $proformaInvoiceTemplate->getIsValid()) {
                        return sprintf(
                            '*%s* %s',
                            $this->translator->trans('invalid'),
                            $proformaInvoiceTemplate->getName()
                        );
                    }

                    return $proformaInvoiceTemplate->getName();
                },
                'selectedProformaInvoiceTemplate' => null,
            ]
        );

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                $selectedProformaInvoiceTemplate = $options['selectedProformaInvoiceTemplate'];

                return function (EntityRepository $entityRepository) use ($selectedProformaInvoiceTemplate) {
                    $queryBuilder = $entityRepository->createQueryBuilder('pit')
                        ->andWhere('pit.deletedAt IS NULL')
                        ->andWhere('pit.isValid = TRUE')
                        ->addOrderBy('pit.name', 'ASC');

                    if ($selectedProformaInvoiceTemplate) {
                        $queryBuilder->orWhere('pit.id = :invoiceTemplate')
                            ->setParameter('invoiceTemplate', $selectedProformaInvoiceTemplate);
                    }

                    return $queryBuilder;
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
