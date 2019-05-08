<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Component\Validator\ValidationErrorCollector;
use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ServiceCreateMap;
use ApiBundle\Map\ServiceMap;
use AppBundle\Entity\Country;
use AppBundle\Entity\Service;
use AppBundle\Entity\State;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Entity\Tax;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Service\ServiceCalculations;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;

class ServiceMapper extends AbstractMapper
{
    /**
     * @var ServiceCalculations
     */
    private $serviceCalculations;

    public function __construct(
        EntityManagerInterface $entityManager,
        Reader $reader,
        ValidationErrorCollector $errorCollector,
        PermissionGrantedChecker $permissionGrantedChecker,
        ServiceCalculations $serviceCalculations
    ) {
        parent::__construct($entityManager, $reader, $errorCollector, $permissionGrantedChecker);

        $this->serviceCalculations = $serviceCalculations;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ServiceMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Service::class;
    }

    /**
     * @param Service $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof ServiceMap) {
            throw new UnexpectedTypeException($map, ServiceMap::class);
        }

        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'street1');
        $this->mapField($entity, $map, 'street2');
        $this->mapField($entity, $map, 'city');
        $this->mapField($entity, $map, 'country', 'countryId', Country::class);
        $this->mapField($entity, $map, 'state', 'stateId', State::class);
        $this->mapField($entity, $map, 'zipCode');
        $this->mapField($entity, $map, 'note');
        $this->mapField($entity, $map, 'addressGpsLat');
        $this->mapField($entity, $map, 'addressGpsLon');
        $this->mapField($entity, $map, 'tariff', 'servicePlanId', Tariff::class);
        $this->mapField($entity, $map, 'tariffPeriod', 'servicePlanPeriodId', TariffPeriod::class);
        $this->mapField($entity, $map, 'individualPrice', 'price');
        $this->mapField($entity, $map, 'invoiceLabel');
        $this->mapField($entity, $map, 'contractId');
        $this->mapField($entity, $map, 'contractLengthType');
        $this->mapField($entity, $map, 'minimumContractLengthMonths');
        $this->mapField($entity, $map, 'activeFrom');
        $this->mapField($entity, $map, 'activeTo');
        $this->mapField($entity, $map, 'contractEndDate');
        $this->mapField($entity, $map, 'discountType');
        $this->mapField($entity, $map, 'discountValue');
        $this->mapField($entity, $map, 'discountInvoiceLabel');
        $this->mapField($entity, $map, 'discountFrom');
        $this->mapField($entity, $map, 'discountTo');
        $this->mapField($entity, $map, 'tax1', 'tax1Id', Tax::class);
        $this->mapField($entity, $map, 'tax2', 'tax2Id', Tax::class);
        $this->mapField($entity, $map, 'tax3', 'tax3Id', Tax::class);
        $this->mapField($entity, $map, 'invoicingStart');
        $this->mapField($entity, $map, 'invoicingPeriodType');
        $this->mapField($entity, $map, 'invoicingPeriodStartDay');
        $this->mapField($entity, $map, 'nextInvoicingDayAdjustment');
        $this->mapField($entity, $map, 'invoicingProratedSeparately');
        $this->mapField($entity, $map, 'invoicingSeparately');
        $this->mapField($entity, $map, 'sendEmailsAutomatically');
        $this->mapField($entity, $map, 'useCreditAutomatically');
        $this->mapField($entity, $map, 'fccBlockId');

        if ($map instanceof ServiceCreateMap && $map->isQuoted) {
            $entity->setStatus(Service::STATUS_QUOTED);
        }
    }

    /**
     * @param Service $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var ServiceMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'clientId', $entity->getClient(), 'id');
        $this->reflectField($map, 'status', $entity->getStatus());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'street1', $entity->getStreet1());
        $this->reflectField($map, 'street2', $entity->getStreet2());
        $this->reflectField($map, 'city', $entity->getCity());
        $this->reflectField($map, 'countryId', $entity->getCountry(), 'id');
        $this->reflectField($map, 'stateId', $entity->getState(), 'id');
        $this->reflectField($map, 'zipCode', $entity->getZipCode());
        $this->reflectField($map, 'note', $entity->getNote());
        $this->reflectField($map, 'addressGpsLat', $entity->getAddressGpsLat());
        $this->reflectField($map, 'addressGpsLon', $entity->getAddressGpsLon());
        $this->reflectField($map, 'servicePlanId', $entity->getTariff(), 'id');
        $this->reflectField($map, 'servicePlanPeriodId', $entity->getTariffPeriod(), 'id');
        $this->reflectField($map, 'price', $entity->getPrice());
        $this->reflectField($map, 'hasIndividualPrice', $entity->getIndividualPrice() !== null);
        $this->reflectField($map, 'currencyCode', $entity->getClient()->getCurrencyCode());
        $this->reflectField($map, 'invoiceLabel', $entity->getInvoiceLabel());
        $this->reflectField($map, 'contractId', $entity->getContractId());
        $this->reflectField($map, 'contractLengthType', $entity->getContractLengthType());
        $this->reflectField($map, 'minimumContractLengthMonths', $entity->getMinimumContractLengthMonths());
        $this->reflectField($map, 'activeFrom', $entity->getActiveFrom());
        $this->reflectField($map, 'activeTo', $entity->getActiveTo());
        $this->reflectField($map, 'contractEndDate', $entity->getContractEndDate());
        $this->reflectField($map, 'discountType', $entity->getDiscountType());
        $this->reflectField($map, 'discountValue', $entity->getDiscountValue());
        $this->reflectField($map, 'discountInvoiceLabel', $entity->getDiscountInvoiceLabel());
        $this->reflectField($map, 'discountFrom', $entity->getDiscountFrom());
        $this->reflectField($map, 'discountTo', $entity->getDiscountTo());
        $this->reflectField($map, 'tax1Id', $entity->getTax1(), 'id');
        $this->reflectField($map, 'tax2Id', $entity->getTax2(), 'id');
        $this->reflectField($map, 'tax3Id', $entity->getTax3(), 'id');
        $this->reflectField($map, 'invoicingStart', $entity->getInvoicingStart());
        $this->reflectField($map, 'invoicingPeriodType', $entity->getInvoicingPeriodType());
        $this->reflectField($map, 'invoicingPeriodStartDay', $entity->getInvoicingPeriodStartDay());
        $this->reflectField($map, 'nextInvoicingDayAdjustment', $entity->getNextInvoicingDayAdjustment());
        $this->reflectField($map, 'invoicingProratedSeparately', $entity->getInvoicingProratedSeparately());
        $this->reflectField($map, 'invoicingSeparately', $entity->getInvoicingSeparately());
        $this->reflectField($map, 'sendEmailsAutomatically', $entity->isSendEmailsAutomatically());
        $this->reflectField($map, 'useCreditAutomatically', $entity->getUseCreditAutomatically());
        $this->reflectField($map, 'servicePlanName', $entity->getTariff(), 'name');
        $this->reflectField($map, 'servicePlanPrice', $entity->getTariffPeriod(), 'price');
        $this->reflectField($map, 'servicePlanPeriod', $entity->getTariffPeriod(), 'period');
        $this->reflectField($map, 'downloadSpeed', $entity->getTariff(), 'downloadSpeed');
        $this->reflectField($map, 'uploadSpeed', $entity->getTariff(), 'uploadSpeed');
        $this->reflectField($map, 'hasOutage', $entity->hasOutage());
        $this->reflectField($map, 'lastInvoicedDate', $entity->getInvoicingLastPeriodEnd());
        $this->reflectField($map, 'totalPrice', $this->serviceCalculations->getTotalPrice($entity));
        $this->reflectField($map, 'fccBlockId', $entity->getFccBlockId());

        $map->ipRanges = [];
        foreach ($entity->getServiceIps() as $serviceIp) {
            $map->ipRanges[] = $serviceIp->getIpRange()->getRange();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'client' => 'clientId',
            'country' => 'countryId',
            'state' => 'stateId',
            'tariff' => 'servicePlanId',
            'tariffPeriod' => 'servicePlanPeriodId',
            'tax1' => 'tax1Id',
            'tax2' => 'tax2Id',
            'tax3' => 'tax3Id',
            'invoicingLastPeriodEnd' => 'lastInvoicedDate',
        ];
    }
}
