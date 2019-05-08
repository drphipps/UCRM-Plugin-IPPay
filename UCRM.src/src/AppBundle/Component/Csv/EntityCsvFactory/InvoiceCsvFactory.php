<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Csv\EntityCsvFactory;

use AppBundle\Component\Csv\CsvBuilder;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class InvoiceCsvFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        Formatter $formatter,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
        $this->translator = $translator;
    }

    public function create(array $ids): ?string
    {
        $builder = new CsvBuilder();

        if (! empty($ids)) {
            $invoices = $this->entityManager->getRepository(Invoice::class)->getExportableByIds($ids);

            /** @var Invoice $invoice */
            foreach ($invoices as $invoice) {
                $data = [
                    'Number' => $invoice->getInvoiceNumber(),
                    'Status' => $this->translator->trans($invoice->getInvoiceStatusName()),
                    'Created date' => $this->formatter->formatDate(
                        $invoice->getCreatedDate(),
                        Formatter::DEFAULT,
                        Formatter::NONE
                    ),
                    'Due date' => $this->formatter->formatDate(
                        $invoice->getDueDate(),
                        Formatter::DEFAULT,
                        Formatter::NONE
                    ),
                    'Total' => $this->formatter->formatCurrency(
                        $invoice->getTotal(),
                        $invoice->getCurrency()->getCode()
                    ),
                    'Taxes' => $this->formatter->formatCurrency(
                        $invoice->getTotalTaxAmount(),
                        $invoice->getCurrency()->getCode()
                    ),
                    'Discount' => $this->formatter->formatCurrency(
                        $invoice->getTotalDiscount(),
                        $invoice->getCurrency()->getCode()
                    ),
                    'Amount paid' => $this->formatter->formatCurrency(
                        $invoice->getAmountPaid(),
                        $invoice->getCurrency()->getCode()
                    ),
                    'Amount due' => $this->formatter->formatCurrency(
                        $invoice->getAmountToPay(),
                        $invoice->getCurrency()->getCode()
                    ),
                    'Organization name' => $invoice->getOrganizationName(),
                    'Client name' => $invoice->getClientNameForView(),
                ];

                $data['Organization address'] = implode(
                    ', ',
                    array_filter(
                        [
                            $invoice->getOrganizationStreet1(),
                            $invoice->getOrganizationStreet2(),
                            $invoice->getOrganizationCity(),
                            $invoice->getOrganizationZipCode(),
                            $invoice->getOrganizationState() ? $invoice->getOrganizationState()->getName() : null,
                            $invoice->getOrganizationCountry() ? $invoice->getOrganizationCountry()->getName() : null,
                        ]
                    )
                );

                if ($invoice->getClientInvoiceAddressSameAsContact()) {
                    $data['Client address'] = implode(
                        ', ',
                        array_filter(
                            [
                                $invoice->getClientStreet1(),
                                $invoice->getClientStreet2(),
                                $invoice->getClientCity(),
                                $invoice->getClientZipCode(),
                                $invoice->getClientState() ? $invoice->getClientState()->getName() : null,
                                $invoice->getClientCountry() ? $invoice->getClientCountry()->getName() : null,
                            ]
                        )
                    );
                } else {
                    $data['Client address'] = implode(
                        ', ',
                        array_filter(
                            [
                                $invoice->getClientInvoiceStreet1(),
                                $invoice->getClientInvoiceStreet2(),
                                $invoice->getClientInvoiceCity(),
                                $invoice->getClientInvoiceZipCode(),
                                $invoice->getClientInvoiceState()
                                    ? $invoice->getClientInvoiceState()->getName()
                                    : null,
                                $invoice->getClientInvoiceCountry()
                                    ? $invoice->getClientInvoiceCountry()->getName()
                                    : null,
                            ]
                        )
                    );
                }

                if ($invoice->getTemplateIncludeTaxInformation()) {
                    $data['Organization registration number'] = $invoice->getOrganizationRegistrationNumber();
                    $data['Organization tax id'] = $invoice->getOrganizationTaxId();
                    $data['Client registration number'] = $invoice->getClientCompanyRegistrationNumber();
                    $data['Client tax id'] = $invoice->getClientCompanyTaxId();
                }

                if ($invoice->getTemplateIncludeBankAccount()) {
                    $data['Organization bank account'] = $invoice->getOrganizationBankAccountFieldsForView();
                }

                $builder->addData($data);
            }
        }

        return $builder->getCsv();
    }
}
