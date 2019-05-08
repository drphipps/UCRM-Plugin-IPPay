<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Service;
use AppBundle\Facade\ServiceFacade;
use AppBundle\Util\Formatter;
use AppBundle\Util\Invoicing;

class ServiceEndDateDataProvider
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var ServiceFacade
     */
    private $serviceFacade;

    private const PERIODS_IN_ADVANCE = 6;

    public function __construct(Formatter $formatter, ServiceFacade $serviceFacade)
    {
        $this->formatter = $formatter;
        $this->serviceFacade = $serviceFacade;
    }

    public function getAvailableEndDates(Service $service): array
    {
        [$from, $to] = Invoicing::getInvoicedPeriodsForm(
            $this->serviceFacade->createClonedService($service),
            new \DateTime(),
            $this->formatter
        );

        return array_flip(array_slice($to, 0, self::PERIODS_IN_ADVANCE));
    }
}
