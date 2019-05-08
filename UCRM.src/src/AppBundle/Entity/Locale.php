<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Cache(region="region_lists")
 */
class Locale
{
    public const DEFAULT_CODE = 'en_US';
    public const DEFAULT_BASE_CODES = [
        'en' => 'en_US',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name = "locale_id", type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(length = 10, unique = true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(length = 50)
     */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
