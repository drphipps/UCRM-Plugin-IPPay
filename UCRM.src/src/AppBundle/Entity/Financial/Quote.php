<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Client;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\LoggableInterface;
use AppBundle\Entity\ParentLoggableInterface;
use AppBundle\Util\Financial\FinancialItemSorter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\QuoteRepository")
 *
 * @UniqueEntity({"quoteNumber", "organization"}, message="This quote number is already used.")
 */
class Quote implements LoggableInterface, ParentLoggableInterface, FinancialInterface
{
    use FinancialTrait;

    public const STATUS_OPEN = 0;
    public const STATUS_ACCEPTED = 1;
    public const STATUS_REJECTED = 2;

    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_REJECTED => 'Rejected',
    ];

    public const VALID_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
    ];

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"unsigned": true, "default": Quote::STATUS_OPEN})
     * @Assert\Choice(choices=Quote::VALID_STATUSES, strict=true)
     */
    protected $status = self::STATUS_OPEN;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Client", inversedBy="quotes")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $client;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=60, nullable=true)
     * @Assert\Length(max = 60)
     */
    protected $quoteNumber;

    /**
     * @var Collection|QuoteItem[]
     *
     * @ORM\OneToMany(targetEntity="QuoteItem", mappedBy="quote", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="quote_id")
     * @ORM\OrderBy({"id" = "ASC"})
     *
     * @Assert\Count(min=1, minMessage="Quote must have at least one item.", groups={FinancialInterface::VALIDATION_GROUP_API})
     * @Assert\Valid()
     */
    protected $quoteItems;

    /**
     * @var QuoteTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\QuoteTemplate")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     */
    protected $quoteTemplate;

    /**
     * @var Collection|EmailLog[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\EmailLog", mappedBy="quote")
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="quote_id")
     */
    protected $emailLogs;

    public function __construct()
    {
        $this->quoteItems = new ArrayCollection();
        $this->emailLogs = new ArrayCollection();
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function setQuoteNumber(?string $quoteNumber): void
    {
        $this->quoteNumber = $quoteNumber;
    }

    public function getQuoteNumber(): ?string
    {
        return $this->quoteNumber;
    }

    public function addQuoteItem(QuoteItem $quoteItem): void
    {
        $this->quoteItems[] = $quoteItem;
    }

    public function removeQuoteItem(QuoteItem $quoteItem): void
    {
        $this->quoteItems->removeElement($quoteItem);
    }

    /**
     * @return Collection|QuoteItem[]
     */
    public function getQuoteItems(): Collection
    {
        return $this->quoteItems;
    }

    public function getQuoteTemplate(): ?QuoteTemplate
    {
        return $this->quoteTemplate;
    }

    public function setQuoteTemplate(?QuoteTemplate $quoteTemplate): void
    {
        $this->quoteTemplate = $quoteTemplate;
    }

    public function addEmailLog(EmailLog $emailLog): void
    {
        $this->emailLogs[] = $emailLog;
    }

    public function removeEmailLog(EmailLog $emailLog): void
    {
        $this->emailLogs->removeElement($emailLog);
    }

    /**
     * @return Collection|EmailLog[]
     */
    public function getEmailLogs(): Collection
    {
        return $this->emailLogs;
    }

    public function addItem(FinancialItemInterface $item): void
    {
        if (! $item instanceof QuoteItem) {
            throw new \InvalidArgumentException('Item not supported.');
        }

        $this->addQuoteItem($item);
    }

    public function removeItem(FinancialItemInterface $item): void
    {
        if (! $item instanceof QuoteItem) {
            throw new \InvalidArgumentException('Item not supported.');
        }

        $this->removeQuoteItem($item);
    }

    /**
     * @return Collection|FinancialItemInterface[]
     */
    public function getItems(): Collection
    {
        return $this->getQuoteItems();
    }

    /**
     * @return Collection|FinancialItemInterface[]
     */
    public function getItemsSorted(): Collection
    {
        return FinancialItemSorter::sort($this->quoteItems);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Quote %s deleted',
            'replacements' => $this->getQuoteNumber(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Quote %s added',
            'replacements' => $this->getQuoteNumber(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns()
    {
        return [
            'amountPaid',
            'emailSentDate',
            'pdfPath',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return $this->getClient();
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
        return $this->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getQuoteNumber(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * Returns quote status as human readable text.
     */
    public function getQuoteStatusName(): string
    {
        return self::STATUSES[$this->status];
    }
}
