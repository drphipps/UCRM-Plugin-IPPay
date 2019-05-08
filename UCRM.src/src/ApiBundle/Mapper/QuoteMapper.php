<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\FinancialItemMap;
use ApiBundle\Map\QuoteMap;
use ApiBundle\Map\TotalTaxMap;
use AppBundle\Entity\Country;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItem;
use AppBundle\Entity\Financial\QuoteItemFee;
use AppBundle\Entity\Financial\QuoteItemOther;
use AppBundle\Entity\Financial\QuoteItemProduct;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Entity\Financial\QuoteItemSurcharge;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Entity\State;
use AppBundle\Entity\Tax;

class QuoteMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return QuoteMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Quote::class;
    }

    /**
     * @param Quote $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof QuoteMap) {
            throw new UnexpectedTypeException($map, QuoteMap::class);
        }

        $this->mapField($entity, $map, 'quoteNumber', 'number');
        $this->mapField($entity, $map, 'createdDate');
        $this->mapField($entity, $map, 'notes');
        $this->mapField($entity, $map, 'comment', 'adminNotes');
        $this->mapField($entity, $map, 'quoteTemplate', 'quoteTemplateId', QuoteTemplate::class);

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
                $quoteItem = new QuoteItemOther();
                $quoteItem->setQuote($entity);

                $this->mapField($quoteItem, $itemMap, 'label');
                $this->mapField($quoteItem, $itemMap, 'price');
                $this->mapField($quoteItem, $itemMap, 'quantity');
                $this->mapField($quoteItem, $itemMap, 'unit');
                $this->mapField($quoteItem, $itemMap, 'tax1', 'tax1Id', Tax::class);
                $this->mapField($quoteItem, $itemMap, 'tax2', 'tax2Id', Tax::class);
                $this->mapField($quoteItem, $itemMap, 'tax3', 'tax3Id', Tax::class);

                $quoteItem->setQuantity($quoteItem->getQuantity() ?? 1.0);
                $quoteItem->calculateTotal();

                $entity->addQuoteItem($quoteItem);
            }
        }
    }

    /**
     * @param Quote $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var QuoteMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'clientId', $entity->getClient(), 'id');
        $this->reflectField($map, 'number', $entity->getQuoteNumber());
        $this->reflectField($map, 'createdDate', $entity->getCreatedDate());
        $this->reflectField($map, 'notes', $entity->getNotes());
        $this->reflectField($map, 'adminNotes', $entity->getComment());
        $this->reflectField($map, 'subtotal', $entity->getSubtotal());
        $this->reflectField($map, 'discount', $entity->getDiscountValue());
        $this->reflectField($map, 'discountLabel', $entity->getDiscountInvoiceLabel());
        $this->reflectField($map, 'total', $entity->getTotal());
        $this->reflectField($map, 'status', $entity->getStatus());
        $this->reflectField($map, 'currencyCode', $entity->getCurrency()->getCode());
        $this->reflectField($map, 'quoteTemplateId', $entity->getQuoteTemplate(), 'id');

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

        foreach ($entity->getQuoteItems() as $quoteItem) {
            $itemMap = new FinancialItemMap();
            $this->reflectField($itemMap, 'id', $quoteItem->getId());
            $this->reflectField($itemMap, 'label', $quoteItem->getLabel());
            $this->reflectField($itemMap, 'price', $quoteItem->getPrice());
            $this->reflectField($itemMap, 'quantity', $quoteItem->getQuantity());
            $this->reflectField($itemMap, 'total', $quoteItem->getTotal());
            $this->reflectField($itemMap, 'tax1Id', $quoteItem->getTax1() ? $quoteItem->getTax1()->getId() : null);
            $this->reflectField($itemMap, 'tax2Id', $quoteItem->getTax2() ? $quoteItem->getTax2()->getId() : null);
            $this->reflectField($itemMap, 'tax3Id', $quoteItem->getTax3() ? $quoteItem->getTax3()->getId() : null);
            $this->reflectField($itemMap, 'type', $this->getQuoteItemType($quoteItem));

            if ($quoteItem instanceof QuoteItemOther || $quoteItem instanceof QuoteItemProduct) {
                $this->reflectField($itemMap, 'unit', $quoteItem->getUnit());
            }

            if ($quoteItem instanceof QuoteItemService || $quoteItem instanceof QuoteItemSurcharge) {
                $this->reflectField($itemMap, 'serviceId', $quoteItem->getService() ? $quoteItem->getService()->getId() : null);
            }

            if ($quoteItem instanceof QuoteItemService) {
                $this->reflectField($itemMap, 'discountPrice', $quoteItem->getDiscountPrice());
                $this->reflectField($itemMap, 'discountQuantity', $quoteItem->getDiscountQuantity());
                $this->reflectField($itemMap, 'discountTotal', $quoteItem->getDiscountTotal());
            }

            if ($quoteItem instanceof QuoteItemSurcharge) {
                $this->reflectField($itemMap, 'serviceSurchargeId', $quoteItem->getServiceSurcharge() ? $quoteItem->getServiceSurcharge()->getId() : null);
            }

            if ($quoteItem instanceof QuoteItemProduct) {
                $this->reflectField($itemMap, 'productId', $quoteItem->getProduct() ? $quoteItem->getProduct()->getId() : null);
            }

            $map->items[] = $itemMap;
        }

        foreach ($entity->getTotalTaxes() as $taxName => $taxTotalValue) {
            $totalTaxMap = new TotalTaxMap();
            $this->reflectField($totalTaxMap, 'name', $taxName);
            $this->reflectField($totalTaxMap, 'totalValue', $taxTotalValue);

            $map->taxes[] = $totalTaxMap;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        $return = [
            'client' => 'clientId',
            'quoteNumber' => 'number',
            'comment' => 'adminNotes',
            'quoteItems' => 'items',
        ];

        for ($i = 0; $i < 100; ++$i) {
            $return[sprintf('quoteItems[%d].label', $i)] = 'items[].label';
            $return[sprintf('quoteItems[%d].price', $i)] = 'items[].price';
            $return[sprintf('quoteItems[%d].quantity', $i)] = 'items[].quantity';
            $return[sprintf('quoteItems[%d].unit', $i)] = 'items[].unit';
            $return[sprintf('quoteItems[%d].tax1', $i)] = 'items[].tax1id';
            $return[sprintf('quoteItems[%d].tax2', $i)] = 'items[].tax2id';
            $return[sprintf('quoteItems[%d].tax3', $i)] = 'items[].tax3id';
        }

        return $return;
    }

    private function getQuoteItemType(QuoteItem $quoteItem): string
    {
        if ($quoteItem instanceof QuoteItemService) {
            return 'service';
        }
        if ($quoteItem instanceof QuoteItemFee) {
            return 'fee';
        }
        if ($quoteItem instanceof QuoteItemProduct) {
            return 'product';
        }
        if ($quoteItem instanceof QuoteItemSurcharge) {
            return 'surcharge';
        }
        if ($quoteItem instanceof QuoteItemOther) {
            return 'other';
        }

        throw new \InvalidArgumentException(sprintf('Unknown quote item type "%s".', get_class($quoteItem)));
    }
}
