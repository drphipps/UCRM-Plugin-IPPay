<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Csv\EntityCsvFactory;

use AppBundle\Component\Csv\CsvBuilder;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class QuoteCsvFactory
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
            $quotes = $this->entityManager->getRepository(Quote::class)->getExportableByIds($ids);

            foreach ($quotes as $quote) {
                $data = [
                    'Number' => $quote->getQuoteNumber(),
                    'Status' => $this->translator->trans($quote->getQuoteStatusName()),
                    'Created date' => $this->formatter->formatDate(
                        $quote->getCreatedDate(),
                        Formatter::DEFAULT,
                        Formatter::NONE
                    ),
                    'Total' => $this->formatter->formatCurrency(
                        $quote->getTotal(),
                        $quote->getCurrency()->getCode()
                    ),
                    'Taxes' => $this->formatter->formatCurrency(
                        $quote->getTotalTaxAmount(),
                        $quote->getCurrency()->getCode()
                    ),
                    'Discount' => $this->formatter->formatCurrency(
                        $quote->getTotalDiscount(),
                        $quote->getCurrency()->getCode()
                    ),
                    'Organization name' => $quote->getOrganizationName(),
                    'Client name' => $quote->getClientNameForView(),
                ];

                $data['Organization address'] = implode(
                    ', ',
                    array_filter(
                        [
                            $quote->getOrganizationStreet1(),
                            $quote->getOrganizationStreet2(),
                            $quote->getOrganizationCity(),
                            $quote->getOrganizationZipCode(),
                            $quote->getOrganizationState() ? $quote->getOrganizationState()->getName() : null,
                            $quote->getOrganizationCountry() ? $quote->getOrganizationCountry()->getName() : null,
                        ]
                    )
                );

                if ($quote->getClientInvoiceAddressSameAsContact()) {
                    $data['Client address'] = implode(
                        ', ',
                        array_filter(
                            [
                                $quote->getClientStreet1(),
                                $quote->getClientStreet2(),
                                $quote->getClientCity(),
                                $quote->getClientZipCode(),
                                $quote->getClientState() ? $quote->getClientState()->getName() : null,
                                $quote->getClientCountry() ? $quote->getClientCountry()->getName() : null,
                            ]
                        )
                    );
                } else {
                    $data['Client address'] = implode(
                        ', ',
                        array_filter(
                            [
                                $quote->getClientInvoiceStreet1(),
                                $quote->getClientInvoiceStreet2(),
                                $quote->getClientInvoiceCity(),
                                $quote->getClientInvoiceZipCode(),
                                $quote->getClientInvoiceState()
                                    ? $quote->getClientInvoiceState()->getName()
                                    : null,
                                $quote->getClientInvoiceCountry()
                                    ? $quote->getClientInvoiceCountry()->getName()
                                    : null,
                            ]
                        )
                    );
                }

                if ($quote->getTemplateIncludeTaxInformation()) {
                    $data['Organization registration number'] = $quote->getOrganizationRegistrationNumber();
                    $data['Organization tax id'] = $quote->getOrganizationTaxId();
                    $data['Client registration number'] = $quote->getClientCompanyRegistrationNumber();
                    $data['Client tax id'] = $quote->getClientCompanyTaxId();
                }

                if ($quote->getTemplateIncludeBankAccount()) {
                    $data['Organization bank account'] = $quote->getOrganizationBankAccountFieldsForView();
                }

                $builder->addData($data);
            }
        }

        return $builder->getCsv();
    }
}
