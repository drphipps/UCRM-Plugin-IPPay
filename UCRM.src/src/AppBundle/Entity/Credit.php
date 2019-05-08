<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CreditRepository")
 */
class Credit implements LoggableInterface, ParentLoggableInterface
{
    public const OVERPAYMENT = 1;
    public const PREPAID_CREDIT = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="credit_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Payment
     *
     * @Assert\NotNull
     * @ORM\OneToOne(targetEntity="Payment", inversedBy="credit")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="payment_id", nullable=false, onDelete="CASCADE")
     */
    private $payment;

    /**
     * @var Client
     *
     * @Assert\NotNull
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="credits")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     */
    private $client;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", nullable=false)
     */
    private $amount;

    /**
     * @ORM\Column(name="type", type="integer")
     */
    private $type;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param float $amount
     *
     * @return Credit
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $type
     *
     * @return Credit
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Credit
     */
    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
        $client->addCredit($this);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return array
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Credit %s deleted',
            'replacements' => $this->getAmount(),
        ];

        return $message;
    }

    /**
     * @return array
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Credit %s added',
            'replacements' => $this->getAmount(),
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
            'message' => $this->getAmount(),
            'entity' => self::class,
        ];

        return $message;
    }
}
