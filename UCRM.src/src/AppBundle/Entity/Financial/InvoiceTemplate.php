<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\LoggableInterface;
use AppBundle\Entity\ParentLoggableInterface;
use AppBundle\Entity\SoftDeleteableTrait;
use AppBundle\Entity\SoftDeleteLoggableInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InvoiceTemplateRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"name"}),
 *     }
 * )
 */
class InvoiceTemplate implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface, FinancialTemplateInterface
{
    use SoftDeleteableTrait;
    use FinancialTemplateTrait;

    /**
     * @var \DateTimeImmutable|null
     */
    private $taxableSupplyDate;

    public function __construct()
    {
        $this->createdDate = new \DateTime();
    }

    public function getTaxableSupplyDate(): ?\DateTimeImmutable
    {
        return $this->taxableSupplyDate;
    }

    public function setTaxableSupplyDate(?\DateTimeImmutable $taxableSupplyDate): void
    {
        $this->taxableSupplyDate = $taxableSupplyDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Invoice template %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Invoice template %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
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
        return null;
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
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArchiveMessage()
    {
        $message['logMsg'] = [
            'message' => 'Invoice template %s archived',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogRestoreMessage()
    {
        $message['logMsg'] = [
            'message' => 'Invoice template %s restored',
            'replacements' => $this->getName(),
        ];

        return $message;
    }
}
