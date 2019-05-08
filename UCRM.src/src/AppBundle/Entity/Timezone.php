<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Timezone
{
    public const DEFAULT_NAME = 'Etc/UTC';

    /**
     * @var int
     *
     * @ORM\Column(name = "timezone_id", type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(length = 50, unique = true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(length = 100)
     */
    private $label;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }
}
