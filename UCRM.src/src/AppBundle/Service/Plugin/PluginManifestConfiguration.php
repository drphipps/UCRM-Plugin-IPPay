<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Plugin;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PluginManifestConfiguration
{
    public const TYPE_CHECKBOX = CheckboxType::class;
    public const TYPE_CHOICE = ChoiceType::class;
    public const TYPE_DATE = DateType::class;
    public const TYPE_DATETIME = DateTimeType::class;
    public const TYPE_FILE = FileType::class;
    public const TYPE_TEXT = TextType::class;
    public const TYPE_TEXTAREA = TextareaType::class;

    public const TYPE_NAMES = [
        'checkbox' => self::TYPE_CHECKBOX,
        'choice' => self::TYPE_CHOICE,
        'date' => self::TYPE_DATE,
        'datetime' => self::TYPE_DATETIME,
        'file' => self::TYPE_FILE,
        'text' => self::TYPE_TEXT,
        'textarea' => self::TYPE_TEXTAREA,
    ];

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string|null
     */
    public $description;

    /**
     * @var bool
     */
    public $required = true;

    /**
     * @var string
     */
    public $type = self::TYPE_TEXT;

    /**
     * @var array
     */
    public $choices = [];
}
