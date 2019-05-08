<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\LoggableInterface;
use AppBundle\Entity\ParentLoggableInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *         "quote_item" = "QuoteItem",
 *         "quote_item_service" = "QuoteItemService",
 *         "quote_item_product" = "QuoteItemProduct",
 *         "quote_item_surcharge" = "QuoteItemSurcharge",
 *         "quote_item_other" = "QuoteItemOther",
 *         "quote_item_fee" = "QuoteItemFee"
 *     })
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\QuoteItemRepository")
 */
class QuoteItem implements LoggableInterface, ParentLoggableInterface, FinancialItemInterface
{
    use FinancialItemTrait;

    /**
     * @var Quote
     *
     * @ORM\ManyToOne(targetEntity="Quote", inversedBy="quoteItems")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $quote;

    public function setQuote(Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function getFinancial(): ?FinancialInterface
    {
        return $this->quote;
    }

    /**
     * @return array
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Quote item %s deleted',
            'replacements' => $this->getLabel(),
        ];

        return $message;
    }

    /**
     * @return array
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Quote item %s added',
            'replacements' => $this->getLabel(),
        ];

        return $message;
    }

    /**
     * @return array
     */
    public function getLogIgnoredColumns()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return $this->getQuote()->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return $this->getQuote();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getLabel(),
            'entity' => self::class,
        ];

        return $message;
    }
}
