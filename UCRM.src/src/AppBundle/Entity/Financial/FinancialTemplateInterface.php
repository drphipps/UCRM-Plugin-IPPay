<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\SoftDeleteableInterface;

interface FinancialTemplateInterface extends SoftDeleteableInterface
{
    public const DEFAULT_TEMPLATE_ID = 1;

    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getCreatedDate(): \DateTime;

    public function setCreatedDate(\DateTime $createdDate): void;

    public function isErrorNotificationSent(): bool;

    public function setErrorNotificationSent(bool $errorNotificationSent): void;

    public function getOfficialName(): ?string;

    public function setOfficialName(?string $officialName): void;

    public function getIsValid(): bool;

    public function setIsValid(bool $isValid): void;
}
