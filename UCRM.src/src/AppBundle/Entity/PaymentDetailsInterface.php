<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

interface PaymentDetailsInterface
{
    public function getProviderId(): int;

    public function getId(): ?int;

    public function setCurrency(?string $currency): void;

    public function getCurrency(): ?string;

    public function getProviderName(): string;

    public function getTransactionId(): ?string;
}
