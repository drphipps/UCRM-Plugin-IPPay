<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial;

use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\FinancialItemProductInterface;
use AppBundle\Entity\Financial\InvoiceItemProduct;
use AppBundle\Entity\Financial\QuoteItemProduct;
use AppBundle\Entity\Product;

class FinancialItemProductFactory
{
    public function createInvoiceItem(Product $product, Client $client): InvoiceItemProduct
    {
        $item = new InvoiceItemProduct();
        $this->setData($item, $product, $client);

        return $item;
    }

    public function createQuoteItem(Product $product, Client $client): QuoteItemProduct
    {
        $item = new QuoteItemProduct();
        $this->setData($item, $product, $client);

        return $item;
    }

    private function setData(FinancialItemProductInterface $item, Product $product, Client $client): void
    {
        $item->setProduct($product);
        $item->setLabel($product->getInvoiceLabel());
        $item->setPrice($product->getPrice());
        $item->setQuantity(1.0);
        $item->setUnit($product->getUnit());
        $item->setTotal($item->getPrice() * $item->getQuantity());

        if ($product->getTaxable()) {
            if ($product->getTax()) {
                $item->setTax1($product->getTax());
            } else {
                $item->setTax1($client->getTax1());
                $item->setTax2($client->getTax2());
                $item->setTax3($client->getTax3());
            }
        }
    }
}
