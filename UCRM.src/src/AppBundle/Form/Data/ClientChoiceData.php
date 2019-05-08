<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Entity\Client;
use AppBundle\Entity\Option;
use Symfony\Component\Translation\TranslatorInterface;

class ClientChoiceData
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var bool
     */
    public $isLead;

    /**
     * @var int|null
     */
    public $clientType;

    /**
     * @var string|null
     */
    public $companyName;

    /**
     * @var string|null
     */
    public $userIdent;

    /**
     * @var string|null
     */
    public $firstName;

    /**
     * @var string|null
     */
    public $lastName;

    /**
     * @var bool
     */
    public $hasBillingEmail;

    /**
     * @var int
     */
    public $currencyId;

    /**
     * @var string
     */
    public $currencyCode;

    /**
     * @var string
     */
    public $currencySymbol;

    /**
     * @var float
     */
    public $accountStandingsRefundableCredit;

    /**
     * @var string|null
     */
    public $locale;

    public function formatName(): ?string
    {
        switch ($this->clientType) {
            case Client::TYPE_RESIDENTIAL:
                return $this->firstName . ' ' . $this->lastName;

            case Client::TYPE_COMPANY:
                return $this->companyName;
        }

        return null;
    }

    public function formatLabel(int $clientIdType, TranslatorInterface $translator): string
    {
        return sprintf(
            '%s (%s: %s)',
            $this->formatName(),
            $clientIdType === Option::CLIENT_ID_TYPE_DEFAULT
                ? $translator->trans('ID')
                : $translator->trans('Custom ID'),
            $clientIdType === Option::CLIENT_ID_TYPE_DEFAULT
                ? $this->id
                : $this->userIdent
        );
    }

    public static function fromArray(array $data): self
    {
        $instance = new self();
        $instance->id = $data['id'];
        $instance->isLead = $data['isLead'];
        $instance->clientType = $data['clientType'];
        $instance->companyName = $data['companyName'];
        $instance->userIdent = $data['userIdent'];
        $instance->firstName = $data['firstName'];
        $instance->lastName = $data['lastName'];
        $instance->hasBillingEmail = $data['hasBillingEmail'];
        $instance->currencyId = $data['currencyId'];
        $instance->currencyCode = $data['currencyCode'];
        $instance->currencySymbol = $data['currencySymbol'];
        $instance->accountStandingsRefundableCredit = $data['accountStandingsRefundableCredit'];
        $instance->locale = $data['locale'];

        return $instance;
    }

    public static function fromClient(Client $client): self
    {
        $instance = new self();
        $instance->id = $client->getId();
        $instance->isLead = $client->getIsLead();
        $instance->clientType = $client->getClientType();
        $instance->companyName = $client->getCompanyName();
        $instance->userIdent = $client->getUserIdent();
        $instance->firstName = $client->getFirstName();
        $instance->lastName = $client->getLastName();
        $instance->hasBillingEmail = $client->hasBillingEmail();
        $instance->currencyId = $client->getOrganization()->getCurrency()->getId();
        $instance->currencyCode = $client->getOrganization()->getCurrency()->getCode();
        $instance->currencySymbol = $client->getOrganization()->getCurrency()->getSymbol();
        $instance->accountStandingsRefundableCredit = $client->getAccountStandingsRefundableCredit();
        $instance->locale = $client->getOrganization()->getLocale();

        return $instance;
    }
}
