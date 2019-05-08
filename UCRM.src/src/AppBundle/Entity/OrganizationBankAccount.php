<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"name"}),
 *     }
 * )
 */
class OrganizationBankAccount implements LoggableInterface, ParentLoggableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="account_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="field1", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    private $field1;

    /**
     * @var string
     *
     * @ORM\Column(name="field2", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    private $field2;

    /**
     * @var Collection|Organization[]
     *
     * @ORM\OneToMany(targetEntity="Organization", mappedBy="bankAccount")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="account_id", nullable=false)
     */
    private $organizations;

    public function __construct()
    {
        $this->organizations = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $field1
     *
     * @return OrganizationBankAccount
     */
    public function setField1($field1)
    {
        $this->field1 = $field1;

        return $this;
    }

    /**
     * @return string
     */
    public function getField1()
    {
        return $this->field1;
    }

    /**
     * @param string $field2
     *
     * @return OrganizationBankAccount
     */
    public function setField2($field2)
    {
        $this->field2 = $field2;

        return $this;
    }

    /**
     * @return string
     */
    public function getField2()
    {
        return $this->field2;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function accountLabel()
    {
        return sprintf('%s (%s)', $this->name, $this->getFieldsForView());
    }

    public function getFieldsForView(): string
    {
        $account = [
            trim($this->field1 ?? ''),
            trim($this->field2 ?? ''),
        ];

        $account = array_filter($account);

        return implode(' / ', $account);
    }

    /**
     * @return Collection|Organization[]
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }

    public function addOrganization(Organization $organization)
    {
        $this->organizations[] = $organization;
        $organization->setBankAccount($this);
    }

    public function removeOrganization(Organization $organization)
    {
        $this->organizations->removeElement($organization);
    }

    /**
     * Get delete message for log.
     *
     * @return array
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Bank account %s deleted',
            'replacements' => $this->getField1() . '/' . $this->getField2(),
        ];

        return $message;
    }

    /**
     * Get insert message for log.
     *
     * @return array
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Bank account %s added',
            'replacements' => $this->getField1() . '/' . $this->getField2(),
        ];

        return $message;
    }

    /**
     * Get unloggable columns for log.
     *
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
            'message' => $this->getField1() . '/' . $this->getField2(),
            'entity' => self::class,
        ];

        return $message;
    }
}
