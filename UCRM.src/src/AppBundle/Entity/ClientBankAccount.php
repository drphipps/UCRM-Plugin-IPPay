<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Util\Strings;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class ClientBankAccount implements LoggableInterface, ParentLoggableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="client_bank_account_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="account_number", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     */
    private $accountNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $stripeBankAccountId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $stripeBankAccountToken;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    private $stripeBankAccountVerified = false;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $stripeCustomerId;

    /**
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="bankAccounts")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", onDelete="CASCADE")
     */
    protected $client;

    /**
     * @var Collection|PaymentStripePending[]
     *
     * @ORM\OneToMany(targetEntity="PaymentStripePending", mappedBy="clientBankAccount", cascade={"remove"})
     * @ORM\JoinColumn(name="client_bank_account_id", referencedColumnName="client_bank_account_id")
     */
    protected $paymentStripePendings;

    public function __construct()
    {
        $this->paymentStripePendings = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setAccountNumber(?string $accountNumber): void
    {
        $this->accountNumber = $accountNumber;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function getStripeBankAccountId(): ?string
    {
        return $this->stripeBankAccountId;
    }

    public function setStripeBankAccountId(?string $stripeBankAccountId): void
    {
        $this->stripeBankAccountId = $stripeBankAccountId;
    }

    public function getStripeBankAccountToken(): ?string
    {
        return $this->stripeBankAccountToken;
    }

    public function setStripeBankAccountToken(?string $stripeBankAccountToken): void
    {
        $this->stripeBankAccountToken = $stripeBankAccountToken;
    }

    public function isStripeBankAccountVerified(): bool
    {
        return $this->stripeBankAccountVerified;
    }

    public function setStripeBankAccountVerified(bool $stripeBankAccountVerified): void
    {
        $this->stripeBankAccountVerified = $stripeBankAccountVerified;
    }

    public function getStripeCustomerId()
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId($stripeCustomerId): void
    {
        $this->stripeCustomerId = $stripeCustomerId;
    }

    /**
     * @param Client $client
     *
     * @return ClientBankAccount
     */
    public function setClient(Client $client = null)
    {
        $this->client = $client;
        $client->addBankAccount($this);

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return array
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Bank account %s deleted',
            'replacements' => Strings::maskBankAccount($this->getAccountNumber()),
        ];

        return $message;
    }

    /**
     * @return array
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Bank account %s added',
            'replacements' => Strings::maskBankAccount($this->getAccountNumber()),
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
            'message' => Strings::maskBankAccount($this->getAccountNumber()),
            'entity' => self::class,
        ];

        return $message;
    }

    public function getPaymentStripePendings()
    {
        return $this->paymentStripePendings;
    }
}
