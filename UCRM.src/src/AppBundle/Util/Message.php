<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Mailing;
use Pelago\Emogrifier;

class Message extends \Swift_Message
{
    /**
     * @var Invoice|null
     */
    private $invoice;

    /**
     * @var Quote|null
     */
    private $quote;

    /**
     * @var Client|null
     */
    private $client;

    /**
     * @var Mailing|null
     */
    private $mailing;

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote)
    {
        $this->quote = $quote;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function setBody($body, $contentType = null, $charset = null)
    {
        // If the message is just text/plain, or the body is NULL (e.g. from constructor), just use the default.
        if ($body === null || $contentType === 'text/plain') {
            parent::setBody($body, $contentType, $charset);

            return $this;
        }

        $emogrifier = new Emogrifier();
        $emogrifier->setHtml($body);
        $body = $emogrifier->emogrify();

        parent::setBody($body, $contentType, $charset);
        parent::addPart(Strings::htmlToPlainText($body), 'text/plain', $charset);

        return $this;
    }

    public function getMailing(): ?Mailing
    {
        return $this->mailing;
    }

    public function setMailing(?Mailing $mailing): void
    {
        $this->mailing = $mailing;
    }
}
