<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DocumentRepository")
 */
class Document implements LoggableInterface
{
    const PATH = '/data/documents';

    const TYPE_DOCUMENT = 'document';
    const TYPE_IMAGE = 'image';
    const TYPE_OTHER = 'other';

    const TYPE_DOCUMENT_EXT = [
        'doc',
        'docx',
        'xls',
        'xlsx',
        'pdf',
        'txt',
        'csv',
        'rtf',
        'ppt',
        'pptx',
        'pps',
        'ppsx',
        'xml',
        'htm',
        'html',
        'odt',
        'sxw',
        'pages',
        'numbers',
        'key',
        'keynote',
    ];
    const TYPE_IMAGE_EXT = [
        'jpg',
        'jpeg',
        'gif',
        'png',
        'bmp',
        'tiff',
        'svg',
        'psd',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="document_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(length=256)
     * @Assert\Length(max=256)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(length=64)
     */
    protected $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var string
     *
     * @ORM\Column(length=1024)
     */
    protected $path;

    /**
     * @var string
     *
     * @ORM\Column(type="bigint")
     */
    protected $size;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", onDelete="SET NULL")
     */
    protected $user;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="documents")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     */
    protected $client;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Document
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Document
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): Document
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): Document
    {
        $this->path = $path;

        return $this;
    }

    public function getSize(): int
    {
        return (int) $this->size;
    }

    public function setSize(int $size): Document
    {
        $this->size = (string) $size;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user = null): Document
    {
        $this->user = $user;

        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): Document
    {
        $this->client = $client;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Document %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Document %s deleted',
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
}
