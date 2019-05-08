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
use ApiBundle\Map\FinancialItemMap;
use ApiBundle\Map\InvoiceAttributeMap;
use ApiBundle\Map\InvoiceMap;
use ApiBundle\Map\PaymentCoverMap;
use ApiBundle\Map\TotalTaxMap;
use AppBundle\Entity\Country;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItem;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Entity\Financial\InvoiceItemOther;
use AppBundle\Entity\Financial\InvoiceItemProduct;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Financial\InvoiceItemSurcharge;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\InvoiceAttribute;
use AppBundle\Entity\State;
use AppBundle\Entity\Tax;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Util\Formatter;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;

class InvoiceMapper extends AbstractMapper
{
    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(
        EntityManagerInterface $entityManager,
        Reader $reader,
        ValidationErrorCollector $errorCollector,
        PermissionGrantedChecker $permissionGrantedChecker,
        Formatter $formatter
    ) {
        parent::__construct($entityManager, $reader, $errorCollector, $permissionGrantedChecker);

        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return InvoiceMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Invoice::class;
    }

    /**
     * @param Invoice $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof InvoiceMap) {
            throw new UnexpectedTypeException($map, InvoiceMap::class);
        }

        $this->mapField($entity, $map, 'invoiceNumber', 'number');
        $this->mapField($entity, $map, 'createdDate');
        $this->mapField($entity, $map, 'emailSentDate');
        $this->mapField($entity, $map, 'invoiceMaturityDays', 'maturityDays');
        $this->mapField($entity, $map, 'notes');
        $this->mapField($entity, $map, 'comment', 'adminNotes');
        $this->mapField($entity, $map, 'invoiceTemplate', 'invoiceTemplateId', InvoiceTemplate::class);

        $this->mapField($entity, $map, 'organizationName');
        $this->mapField($entity, $map, 'organizationRegistrationNumber');
        $this->mapField($entity, $map, 'organizationTaxId');
        $this->mapField($entity, $map, 'organizationStreet1');
        $this->mapField($entity, $map, 'organizationStreet2');
        $this->mapField($entity, $map, 'organizationCity');
        $this->mapField($entity, $map, 'organizationCountry', 'organizationCountryId', Country::class);
        $this->mapField($entity, $map, 'organizationState', 'organizationStateId', State::class);
        $this->mapField($entity, $map, 'organizationZipCode');
        $this->mapField($entity, $map, 'organizationBankAccountName');
        $this->mapField($entity, $map, 'organizationBankAccountField1');
        $this->mapField($entity, $map, 'organizationBankAccountField2');

        $this->mapField($entity, $map, 'clientFirstName');
        $this->mapField($entity, $map, 'clientLastName');
        $this->mapField($entity, $map, 'clientCompanyName');
        $this->mapField($entity, $map, 'clientCompanyRegistrationNumber');
        $this->mapField($entity, $map, 'clientCompanyTaxId');

        if ($entity->getClientInvoiceAddressSameAsContact()) {
            $this->mapField($entity, $map, 'clientStreet1');
            $this->mapField($entity, $map, 'clientStreet2');
            $this->mapField($entity, $map, 'clientCity');
            $this->mapField($entity, $map, 'clientCountry', 'clientCountryId', Country::class);
            $this->mapField($entity, $map, 'clientState', 'clientStateId', State::class);
            $this->mapField($entity, $map, 'clientZipCode');
        } else {
            $this->mapField($entity, $map, 'clientInvoiceStreet1', 'clientStreet1');
            $this->mapField($entity, $map, 'clientInvoiceStreet2', 'clientStreet2');
            $this->mapField($entity, $map, 'clientInvoiceCity', 'clientCity');
            $this->mapField($entity, $map, 'clientInvoiceCountry', 'clientCountryId', Country::class);
            $this->mapField($entity, $map, 'clientInvoiceState', 'clientStateId', State::class);
            $this->mapField($entity, $map, 'clientInvoiceZipCode', 'clientZipCode');
        }

        if ($map->items !== null) {
            /** @var FinancialItemMap $itemMap */
            foreach ($map->items as $itemMap) {
                $invoiceItem = new InvoiceItemOther();
                $invoiceItem->setInvoice($entity);

                $this->mapField($invoiceItem, $itemMap, 'label');
                $this->mapField($invoiceItem, $itemMap, 'price');
                $this->mapField($invoiceItem, $itemMap, 'quantity');
                $this->mapField($invoiceItem, $itemMap, 'unit');
                $this->mapField($invoiceItem, $itemMap, 'tax1', 'tax1Id', Tax::class);
                $this->mapField($invoiceItem, $itemMap, 'tax2', 'tax2Id', Tax::class);
                $this->mapField($invoiceItem, $itemMap, 'tax3', 'tax3Id', Tax::class);

                $invoiceItem->setQuantity($invoiceItem->getQuantity() ?? 1.0);
                $invoiceItem->calculateTotal();

                $entity->addInvoiceItem($invoiceItem);
            }
        }

        $invoiceAttributes = [];
        foreach ($entity->getAttributes() as $attribute) {
            $invoiceAttributes[$attribute->getAttribute()->getId()] = $attribute;
        }

        /** @var InvoiceAttributeMap $attributeMap */
        foreach ($map->attributes ?? [] as $attributeMap) {
            $attribute = $invoiceAttributes[$attributeMap->customAttributeId] ?? null;

            if ($attributeMap->value !== null && $attributeMap->value !== '') {
                if (! $attribute) {
                    $attribute = new InvoiceAttribute();
                    $entity->addAttribute($attribute);
                }
                $attribute->setInvoice($entity);

                $this->mapField($attribute, $attributeMap, 'value');
                $this->mapField($attribute, $attributeMap, 'attribute', 'customAttributeId', CustomAttribute::class);
            } elseif ($attribute) {
                $entity->removeAttribute($attribute);
            }
        }
    }

    /**
     * @param Invoice $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var InvoiceMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'clientId', $entity->getClient(), 'id');
        $this->reflectField($map, 'number', $entity->getInvoiceNumber());
        $this->reflectField($map, 'createdDate', $entity->getCreatedDate());
        $this->reflectField($map, 'dueDate', $entity->getDueDate());
        $this->reflectField($map, 'emailSentDate', $entity->getEmailSentDate());
        $this->reflectField($map, 'maturityDays', $entity->getInvoiceMaturityDays());
        $this->reflectField($map, 'taxableSupplyDate', $entity->getTaxableSupplyDate());
        $this->reflectField($map, 'notes', $entity->getNotes());
        $this->reflectField($map, 'adminNotes', $entity->getComment());
        $this->reflectField($map, 'subtotal', $entity->getSubtotal());
        $this->reflectField($map, 'discount', $entity->getDiscountValue());
        $this->reflectField($map, 'discountLabel', $entity->getDiscountInvoiceLabel());
        $this->reflectField($map, 'total', $entity->getTotal());
        $this->reflectField($map, 'amountPaid', $entity->getAmountPaid());
        $this->reflectField($map, 'status', $entity->getInvoiceStatus());
        $this->reflectField($map, 'currencyCode', $entity->getCurrency()->getCode());
        $this->reflectField($map, 'invoiceTemplateId', $entity->getInvoiceTemplate(), 'id');

        $this->reflectField($map, 'organizationName', $entity->getOrganizationName());
        $this->reflectField($map, 'organizationRegistrationNumber', $entity->getOrganizationRegistrationNumber());
        $this->reflectField($map, 'organizationTaxId', $entity->getOrganizationTaxId());
        $this->reflectField($map, 'organizationStreet1', $entity->getOrganizationStreet1());
        $this->reflectField($map, 'organizationStreet2', $entity->getOrganizationStreet2());
        $this->reflectField($map, 'organizationCity', $entity->getOrganizationCity());
        $this->reflectField(
            $map,
            'organizationCountryId',
            $entity->getOrganizationCountry() ? $entity->getOrganizationCountry()->getId() : null
        );
        $this->reflectField(
            $map,
            'organizationStateId',
            $entity->getOrganizationState() ? $entity->getOrganizationState()->getId() : null
        );
        $this->reflectField($map, 'organizationZipCode', $entity->getOrganizationZipCode());
        $this->reflectField($map, 'organizationBankAccountName', $entity->getOrganizationBankAccountName());
        $this->reflectField($map, 'organizationBankAccountField1', $entity->getOrganizationBankAccountField1());
        $this->reflectField($map, 'organizationBankAccountField2', $entity->getOrganizationBankAccountField2());

        $this->reflectField($map, 'clientFirstName', $entity->getClientFirstName());
        $this->reflectField($map, 'clientLastName', $entity->getClientLastName());
        $this->reflectField($map, 'clientCompanyName', $entity->getClientCompanyName());
        $this->reflectField($map, 'clientCompanyRegistrationNumber', $entity->getClientCompanyRegistrationNumber());
        $this->reflectField($map, 'clientCompanyTaxId', $entity->getClientCompanyTaxId());

        if ($entity->getClientInvoiceAddressSameAsContact()) {
            $this->reflectField($map, 'clientStreet1', $entity->getClientStreet1());
            $this->reflectField($map, 'clientStreet2', $entity->getClientStreet2());
            $this->reflectField($map, 'clientCity', $entity->getClientCity());
            $this->reflectField(
                $map,
                'clientCountryId',
                $entity->getClientCountry() ? $entity->getClientCountry()->getId() : null
            );
            $this->reflectField(
                $map,
                'clientStateId',
                $entity->getClientState() ? $entity->getClientState()->getId() : null
            );
            $this->reflectField($map, 'clientZipCode', $entity->getClientZipCode());
        } else {
            $this->reflectField($map, 'clientStreet1', $entity->getClientInvoiceStreet1());
            $this->reflectField($map, 'clientStreet2', $entity->getClientInvoiceStreet2());
            $this->reflectField($map, 'clientCity', $entity->getClientInvoiceCity());
            $this->reflectField(
                $map,
                'clientCountryId',
                $entity->getClientInvoiceCountry() ? $entity->getClientInvoiceCountry()->getId() : null
            );
            $this->reflectField(
                $map,
                'clientStateId',
                $entity->getClientInvoiceState() ? $entity->getClientInvoiceState()->getId() : null
            );
            $this->reflectField($map, 'clientZipCode', $entity->getClientInvoiceZipCode());
        }

        foreach ($entity->getInvoiceItems() as $invoiceItem) {
            $itemMap = new FinancialItemMap();
            $this->reflectField($itemMap, 'id', $invoiceItem->getId());
            $this->reflectField($itemMap, 'label', $this->formatServiceItemLabel($invoiceItem));
            $this->reflectField($itemMap, 'price', $invoiceItem->getPrice());
            $this->reflectField($itemMap, 'quantity', $invoiceItem->getQuantity());
            $this->reflectField($itemMap, 'total', $invoiceItem->getTotal());
            $this->reflectField($itemMap, 'tax1Id', $invoiceItem->getTax1() ? $invoiceItem->getTax1()->getId() : null);
            $this->reflectField($itemMap, 'tax2Id', $invoiceItem->getTax2() ? $invoiceItem->getTax2()->getId() : null);
            $this->reflectField($itemMap, 'tax3Id', $invoiceItem->getTax3() ? $invoiceItem->getTax3()->getId() : null);
            $this->reflectField($itemMap, 'type', $this->getInvoiceItemType($invoiceItem));

            if ($invoiceItem instanceof InvoiceItemOther || $invoiceItem instanceof InvoiceItemProduct) {
                $this->reflectField($itemMap, 'unit', $invoiceItem->getUnit());
            }

            if ($invoiceItem instanceof InvoiceItemService || $invoiceItem instanceof InvoiceItemSurcharge) {
                $this->reflectField($itemMap, 'serviceId', $invoiceItem->getService() ? $invoiceItem->getService()->getId() : null);
            }

            if ($invoiceItem instanceof InvoiceItemService) {
                $this->reflectField($itemMap, 'discountPrice', $invoiceItem->getDiscountPrice());
                $this->reflectField($itemMap, 'discountQuantity', $invoiceItem->getDiscountQuantity());
                $this->reflectField($itemMap, 'discountTotal', $invoiceItem->getDiscountTotal());
            }

            if ($invoiceItem instanceof InvoiceItemSurcharge) {
                $this->reflectField($itemMap, 'serviceSurchargeId', $invoiceItem->getServiceSurcharge() ? $invoiceItem->getServiceSurcharge()->getId() : null);
            }

            if ($invoiceItem instanceof InvoiceItemProduct) {
                $this->reflectField($itemMap, 'productId', $invoiceItem->getProduct() ? $invoiceItem->getProduct()->getId() : null);
            }

            $map->items[] = $itemMap;
        }

        foreach ($entity->getPaymentCovers() as $paymentCover) {
            $paymentCoverMap = new PaymentCoverMap();
            $this->reflectField($paymentCoverMap, 'id', $paymentCover->getId());
            $this->reflectField($paymentCoverMap, 'invoiceId', $paymentCover->getInvoice(), 'id');
            $this->reflectField($paymentCoverMap, 'paymentId', $paymentCover->getPayment(), 'id');
            $this->reflectField($paymentCoverMap, 'refundId', $paymentCover->getRefund(), 'id');
            $this->reflectField($paymentCoverMap, 'amount', $paymentCover->getAmount());

            $map->paymentCovers[] = $paymentCoverMap;
        }

        foreach ($entity->getTotalTaxes() as $taxName => $taxTotalValue) {
            $totalTaxMap = new TotalTaxMap();
            $this->reflectField($totalTaxMap, 'name', $taxName);
            $this->reflectField($totalTaxMap, 'totalValue', $taxTotalValue);

            $map->taxes[] = $totalTaxMap;
        }

        foreach ($entity->getAttributes() as $attribute) {
            $attributeMap = new InvoiceAttributeMap();
            $this->reflectField($attributeMap, 'id', $attribute->getId());
            $this->reflectField($attributeMap, 'invoiceId', $entity->getId());
            $this->reflectField($attributeMap, 'customAttributeId', $attribute->getAttribute()->getId());
            $this->reflectField($attributeMap, 'name', $attribute->getAttribute()->getName());
            $this->reflectField($attributeMap, 'key', $attribute->getAttribute()->getKey());
            $this->reflectField($attributeMap, 'value', $attribute->getValue());

            $map->attributes[] = $attributeMap;
        }

        $this->reflectField($map, 'uncollectible', $entity->isUncollectible());
        $this->reflectField($map, 'proforma', $entity->isProforma());
        $this->reflectField(
            $map,
            'proformaInvoiceId',
            $entity->getProformaInvoice() ? $entity->getProformaInvoice()->getId() : null
        );
        $this->reflectField(
            $map,
            'generatedInvoiceId',
            $entity->getGeneratedInvoice() ? $entity->getGeneratedInvoice()->getId() : null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        $return = [
            'client' => 'clientId',
            'invoiceNumber' => 'number',
            'invoiceMaturityDays' => 'maturityDays',
            'comment' => 'adminNotes',
            'invoiceItems' => 'items',
        ];

        for ($i = 0; $i < 100; ++$i) {
            $return[sprintf('invoiceItems[%d].label', $i)] = 'items[].label';
            $return[sprintf('invoiceItems[%d].price', $i)] = 'items[].price';
            $return[sprintf('invoiceItems[%d].quantity', $i)] = 'items[].quantity';
            $return[sprintf('invoiceItems[%d].unit', $i)] = 'items[].unit';
            $return[sprintf('invoiceItems[%d].tax1', $i)] = 'items[].tax1id';
            $return[sprintf('invoiceItems[%d].tax2', $i)] = 'items[].tax2id';
            $return[sprintf('invoiceItems[%d].tax3', $i)] = 'items[].tax3id';
        }

        return $return;
    }

    private function formatServiceItemLabel(InvoiceItem $invoiceItem): string
    {
        if (! $invoiceItem instanceof InvoiceItemService) {
            return $invoiceItem->getLabel();
        }

        return sprintf(
            '%s %s %s %s',
            $invoiceItem->getLabel(),
            $invoiceItem->getInvoicedFrom() ? $this->formatDate($invoiceItem->getInvoicedFrom()) : '',
            html_entity_decode('&ndash;'),
            $invoiceItem->getInvoicedTo() ? $this->formatDate($invoiceItem->getInvoicedTo()) : ''
        );
    }

    private function formatDate(\DateTimeInterface $date): string
    {
        return Strings::replace(
            $this->formatter->formatDate(
                $date,
                Formatter::DEFAULT,
                Formatter::NONE
            ),
            '/ /',
            html_entity_decode('&nbsp;')
        );
    }

    private function getInvoiceItemType(InvoiceItem $invoiceItem): string
    {
        if ($invoiceItem instanceof InvoiceItemService) {
            return 'service';
        }
        if ($invoiceItem instanceof InvoiceItemFee) {
            return 'fee';
        }
        if ($invoiceItem instanceof InvoiceItemProduct) {
            return 'product';
        }
        if ($invoiceItem instanceof InvoiceItemSurcharge) {
            return 'surcharge';
        }
        if ($invoiceItem instanceof InvoiceItemOther) {
            return 'other';
        }

        throw new \InvalidArgumentException(sprintf('Unknown invoice item type "%s".', get_class($invoiceItem)));
    }
}
