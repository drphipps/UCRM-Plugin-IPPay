<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace RabbitMqBundle;

interface MessageInterface
{
    public function getProducer(): string;

    public function getBody(): string;

    public function getBodyProperties(): array;

    public function getRoutingKey(): string;

    public function getProperties(): array;
}
