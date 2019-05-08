<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Transformer;

use AppBundle\Component\Import\Builder\ClientErrorSummaryBuilder;
use AppBundle\Component\Import\Builder\ServiceImportItemValidationErrorsBuilder;
use AppBundle\Component\Import\DataProvider\TransformerEntityData;
use AppBundle\Entity\Client;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Import\ServiceImportItem;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Factory\ServiceFactory;
use AppBundle\Service\Options;
use Nette\Utils\Strings;
use Symfony\Component\Translation\TranslatorInterface;

class ServiceImportItemToServiceTransformer extends AbstractImportItemToEntityTransformer
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    public function __construct(
        Options $options,
        TranslatorInterface $translator,
        ServiceFactory $serviceFactory
    ) {
        $this->options = $options;
        $this->translator = $translator;
        $this->serviceFactory = $serviceFactory;
    }

    public function transform(
        ServiceImportItem $item,
        Client $client,
        TransformerEntityData $transformerEntityData,
        ServiceImportItemValidationErrorsBuilder $validationErrorsBuilder,
        ?ClientErrorSummaryBuilder $errorSummaryBuilder
    ): Service {
        $service = $this->serviceFactory->create($client);
        if ($client->getIsLead()) {
            $service->setStatus(Service::STATUS_QUOTED);
        }

        $service->setTariff(
            $this->transformServicePlan(
                $item->getTariff(),
                $item->getImportItem()->getImport()->getOrganization(),
                $transformerEntityData,
                $validationErrorsBuilder,
                $errorSummaryBuilder
            )
            ?? $service->getTariff()
        );

        $service->setTariffPeriod(
            $this->transformServicePlanPeriod(
                $item->getTariffPeriod(),
                $service->getTariff(),
                $validationErrorsBuilder
            )
            ?? $service->getTariffPeriod()
        );

        $service->setActiveFrom(
            $this->transformDate($item->getActiveFrom(), 'activeFrom', $validationErrorsBuilder)
            ?? $service->getActiveFrom()
        );
        $service->setActiveTo(
            $this->transformDate($item->getActiveTo(), 'activeTo', $validationErrorsBuilder)
            ?? $service->getActiveTo()
        );
        $service->setInvoicingStart(
            $this->transformDate($item->getInvoicingStart(), 'invoicingStart', $validationErrorsBuilder)
            ?? $service->getInvoicingStart()
        );
        $service->setContractEndDate(
            $this->transformDate($item->getContractEndDate(), 'contractEndDate', $validationErrorsBuilder)
            ?? $service->getContractEndDate()
        );

        $service->setTax1(
            $this->transformTax(
                $item->getTax1(),
                'tax1',
                $transformerEntityData,
                $validationErrorsBuilder,
                $errorSummaryBuilder
            )
            ?? $service->getTax1()
        );
        $service->setTax2(
            $this->transformTax(
                $item->getTax2(),
                'tax2',
                $transformerEntityData,
                $validationErrorsBuilder,
                $errorSummaryBuilder
            )
            ?? $service->getTax2()
        );
        $service->setTax3(
            $this->transformTax(
                $item->getTax3(),
                'tax3',
                $transformerEntityData,
                $validationErrorsBuilder,
                $errorSummaryBuilder
            )
            ?? $service->getTax3()
        );

        $service->setInvoiceLabel($item->getInvoiceLabel());
        $service->setNote($item->getNote());

        $service->setIndividualPrice(
            $this->transformFloat(
                $item->getIndividualPrice(),
                'individualPrice',
                'Individual price should be a valid number.',
                $validationErrorsBuilder
            )
            ?? $service->getIndividualPrice()
        );

        $service->setInvoicingSeparately(
            $this->transformBool($item->getInvoiceSeparately()) ?? $service->getInvoicingSeparately()
        );

        $service->setUseCreditAutomatically(
            $this->transformBool($item->getInvoiceUseCredit()) ?? $service->getUseCreditAutomatically()
        );

        $service->setSendEmailsAutomatically(
            $this->transformBool($item->getInvoiceApproveSendAuto()) ?? $service->isSendEmailsAutomatically()
        );

        $service->setFccBlockId($item->getFccBlockId());
        $service->setContractId($item->getContractId());

        $service->setAddressGpsLat(
            $this->transformFloat(
                $item->getAddressGpsLat(),
                'addressGpsLat',
                'Service latitude should be a valid number.',
                $validationErrorsBuilder
            )
            ?? $service->getAddressGpsLat()
        );
        $service->setAddressGpsLon(
            $this->transformFloat(
                $item->getAddressGpsLon(),
                'addressGpsLon',
                'Service longitude should be a valid number.',
                $validationErrorsBuilder
            )
            ?? $service->getAddressGpsLon()
        );

        $service->setMinimumContractLengthMonths(
            $this->transformInt(
                $item->getMinimumContractLengthMonths(),
                'minimumContractLengthMonths',
                'Minimum contact length should be a valid number.',
                $validationErrorsBuilder
            )
            ?? $service->getMinimumContractLengthMonths()
        );

        $service->setInvoicingPeriodType(
            $this->transformInvoicingPeriodType(
                $item->getInvoicingPeriodType(),
                $validationErrorsBuilder
            )
            ?? $service->getInvoicingPeriodType()
        );

        $service->setInvoicingPeriodStartDay(
            $this->transformInt(
                $item->getInvoicingPeriodStartDay(),
                'invoicingPeriodStartDay',
                'Service invoicing period start day should be a valid number.',
                $validationErrorsBuilder
            )
            ?? $service->getInvoicingPeriodStartDay()
        );

        $service->setNextInvoicingDayAdjustment(
            $this->transformInt(
                $item->getInvoicingDaysInAdvance(),
                'invoicingDaysInAdvance',
                'Service create invoice X days in advance should be a valid number.',
                $validationErrorsBuilder
            )
            ?? $service->getNextInvoicingDayAdjustment()
        );

        $service->setSetupFee(
            $this->createSetupFee(
                $item->getSetupFee(),
                $service->getClient(),
                $service->getActiveFrom(),
                $validationErrorsBuilder
            )
        );

        $service->setContractLengthType(
            $this->transformContractLengthType(
                $item->getContractType(),
                $validationErrorsBuilder
            )
            ?? $service->getContractLengthType()
        );

        $service->setEarlyTerminationFeePrice(
            $this->transformFloat(
                $item->getEarlyTerminationFee(),
                'earlyTerminationFee',
                'Early termination fee should be a valid number.',
                $validationErrorsBuilder
            )
            ?? $service->getEarlyTerminationFeePrice()
        );

        return $service;
    }

    private function transformServicePlan(
        ?string $value,
        Organization $organization,
        TransformerEntityData $transformerEntityData,
        ServiceImportItemValidationErrorsBuilder $validationErrorsBuilder,
        ?ClientErrorSummaryBuilder $errorSummaryBuilder
    ): ?Tariff {
        if ($value === null) {
            return null;
        }

        $servicePlan = $transformerEntityData->getServicePlan(
            $value,
            $organization
        );

        if (! $servicePlan) {
            $validationErrorsBuilder->addTransformerViolation(
                'Service plan %plan% not found.',
                'tariff',
                $value,
                [
                    '%plan%' => $value,
                ]
            );

            if ($errorSummaryBuilder) {
                $errorSummaryBuilder->addMissingServicePlan($value);
            }

            return null;
        }

        return $servicePlan;
    }

    private function transformServicePlanPeriod(
        ?string $value,
        ?Tariff $servicePlan,
        ServiceImportItemValidationErrorsBuilder $validationErrorsBuilder
    ): ?TariffPeriod {
        if ($value === null || $servicePlan === null) {
            return null;
        }

        $period = $servicePlan->getPeriodByPeriod((int) $value);
        if (! $period || ! $period->isEnabled()) {
            $months = [];
            foreach ($servicePlan->getEnabledPeriods() as $period) {
                $months[] = $period->getPeriod();
            }

            $validationErrorsBuilder->addTransformerViolation(
                'Service period should be a number of months: %months%.',
                'tariffPeriod',
                $value,
                [
                    '%months%' => implode(', ', $months),
                ]
            );

            return null;
        }

        return $period;
    }

    private function transformInvoicingPeriodType(
        ?string $value,
        ServiceImportItemValidationErrorsBuilder $validationErrorsBuilder
    ): ?int {
        if ($value === null) {
            return null;
        }

        switch (Strings::lower(trim($value))) {
            case 'b':
            case 'back':
            case 'backward':
            case 'backwards':
                return Service::INVOICING_BACKWARDS;
            case 'f':
            case 'fwd':
            case 'forward':
            case 'forwards':
                return Service::INVOICING_FORWARDS;
        }

        $validationErrorsBuilder->addTransformerViolation(
            'Invoicing period type should be "forward" or "backward".',
            'invoicingPeriodType',
            $value
        );

        return null;
    }

    private function transformContractLengthType(
        ?string $value,
        ServiceImportItemValidationErrorsBuilder $validationErrorsBuilder
    ): ?int {
        if ($value === null) {
            return null;
        }

        switch (Strings::lower(trim($value))) {
            case 'c':
            case 'close':
            case 'closed':
                return Service::CONTRACT_CLOSED;
            case 'o':
            case 'open':
            case 'opened':
                return Service::CONTRACT_OPEN;
        }

        $validationErrorsBuilder->addTransformerViolation(
            'Contract type should be "open" or "closed".',
            'contractType',
            $value
        );

        return null;
    }

    private function createSetupFee(
        ?string $value,
        ?Client $client,
        ?\DateTime $createdDate,
        ServiceImportItemValidationErrorsBuilder $validationErrorsBuilder
    ): ?Fee {
        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            $validationErrorsBuilder->addTransformerViolation(
                'Setup fee should be a valid number.',
                'setupFee',
                $value
            );

            return null;
        }

        $setupFee = new Fee();
        $setupFee->setClient($client);
        $setupFee->setName(
            $this->options->get(Option::SETUP_FEE_INVOICE_LABEL) ?? $this->translator->trans('import/Service setup fee')
        );
        $setupFee->setType(Fee::TYPE_SETUP_FEE);
        $setupFee->setPrice((float) $value);
        $setupFee->setTaxable((bool) $this->options->get(Option::SETUP_FEE_TAXABLE));
        $setupFee->setCreatedDate($createdDate ?? new \DateTime());

        return $setupFee;
    }
}
