<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Csv\EntityCsvFactory;

use AppBundle\Component\Csv\CsvBuilder;
use AppBundle\DataProvider\ClientLogsViewDataProvider;
use AppBundle\Entity\ClientLogsView;
use AppBundle\Service\ClientLogsView\ClientLogsViewConverter;

class ClientLogsViewCsvFactory
{
    /**
     * @var ClientLogsViewConverter
     */
    private $clientLogsConverter;

    /**
     * @var ClientLogsViewDataProvider
     */
    private $clientLogsViewDataProvider;

    public function __construct(
        ClientLogsViewConverter $clientLogsConverter,
        ClientLogsViewDataProvider $clientLogsViewDataProvider
    ) {
        $this->clientLogsConverter = $clientLogsConverter;
        $this->clientLogsViewDataProvider = $clientLogsViewDataProvider;
    }

    public function create(array $ids): string
    {
        $builder = new CsvBuilder();

        $clientLogsViews = $this->clientLogsViewDataProvider->getByIds($ids);
        /** @var ClientLogsView $clientLogsView */
        foreach ($clientLogsViews as $clientLogsView) {
            $clientLogsConverted = $this->clientLogsConverter->convertToRowDataForView($clientLogsView);

            $builder->addData(
                [
                    'Type' => $clientLogsConverted->logType,
                    'Message' => $clientLogsConverted->message,
                    'User' => $clientLogsConverted->user,
                    'Created date' => $clientLogsConverted->createdDate,
                    'System log details' => $clientLogsConverted->entityLogDetails,
                ]
            );
        }

        return $builder->getCsv();
    }
}
