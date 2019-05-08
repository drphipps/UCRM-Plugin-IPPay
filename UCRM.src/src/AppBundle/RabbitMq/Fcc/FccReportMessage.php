<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Fcc;

use AppBundle\Entity\Download;
use AppBundle\Entity\Organization;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class FccReportMessage implements MessageInterface
{
    public const TYPE_FIXED_BROADBAND_DEPLOYMENT = 'Fixed Broadband Deployment';
    public const TYPE_FIXED_BROADBAND_SUBSCRIPTION = 'Fixed Broadband Subscription';

    /**
     * @var Download
     */
    private $download;

    /**
     * @var array|Organization[]
     */
    private $organizations;

    /**
     * @var string
     */
    private $type;

    public function __construct(Download $download, array $organizations, string $type)
    {
        $this->download = $download;
        $this->organizations = $organizations;
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'fcc_report';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        $ids = [];
        foreach ($this->organizations as $organization) {
            $ids[] = $organization->getId();
        }

        return Json::encode(
            [
                'download' => $this->download->getId(),
                'organizations' => $ids,
                'type' => $this->type,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'download',
            'organizations',
            'type',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'fcc_report';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
