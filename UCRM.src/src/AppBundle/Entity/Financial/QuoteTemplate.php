<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\LoggableInterface;
use AppBundle\Entity\ParentLoggableInterface;
use AppBundle\Entity\SoftDeleteableTrait;
use AppBundle\Entity\SoftDeleteLoggableInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\QuoteTemplateRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"name"}),
 *     }
 * )
 */
class QuoteTemplate implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface, FinancialTemplateInterface
{
    use SoftDeleteableTrait;
    use FinancialTemplateTrait;

    public function __construct()
    {
        $this->createdDate = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Quote template %s deleted',
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
            'message' => 'Quote template %s added',
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
            'message' => 'Quote template %s archived',
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
            'message' => 'Quote template %s restored',
            'replacements' => $this->getName(),
        ];

        return $message;
    }
}
