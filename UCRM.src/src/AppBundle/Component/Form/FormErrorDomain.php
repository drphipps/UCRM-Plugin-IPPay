<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Form;

use Symfony\Component\Form\FormError;

class FormErrorDomain extends FormError
{
    /**
     * @var string|null
     */
    private $translationDomain;

    public function __construct(string $message, ?string $translationDomain = 'validators', ?string $messageTemplate = null, array $messageParameters = [], ?int $messagePluralization = null, $cause = null)
    {
        parent::__construct($message, $messageTemplate, $messageParameters, $messagePluralization, $cause);
        $this->translationDomain = $translationDomain;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }
}
