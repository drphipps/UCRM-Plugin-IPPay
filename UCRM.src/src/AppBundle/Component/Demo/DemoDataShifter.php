<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo;

use AppBundle\Component\Demo\ShiftProvider\ClientShiftProvider;
use AppBundle\Component\Demo\ShiftProvider\FeeShiftProvider;
use AppBundle\Component\Demo\ShiftProvider\InvoiceShiftProvider;
use AppBundle\Component\Demo\ShiftProvider\PaymentShiftProvider;
use AppBundle\Component\Demo\ShiftProvider\QuoteShiftProvider;
use AppBundle\Component\Demo\ShiftProvider\ServiceShiftProvider;
use AppBundle\Entity\General;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeImmutableFactory;
use Doctrine\ORM\EntityManagerInterface;

class DemoDataShifter
{
    // the original timestamp of demo data
    private const BASE_DATE = '2018-09-19';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    /**
     * @var InvoiceShiftProvider
     */
    private $invoiceShiftProvider;

    /**
     * @var ClientShiftProvider
     */
    private $clientShiftProvider;

    /**
     * @var FeeShiftProvider
     */
    private $feeShiftProvider;

    /**
     * @var PaymentShiftProvider
     */
    private $paymentShiftProvider;

    /**
     * @var QuoteShiftProvider
     */
    private $quoteShiftProvider;

    /**
     * @var ServiceShiftProvider
     */
    private $serviceShiftProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        OptionsFacade $optionsFacade,
        InvoiceShiftProvider $invoiceShiftProvider,
        ClientShiftProvider $clientShiftProvider,
        FeeShiftProvider $feeShiftProvider,
        PaymentShiftProvider $paymentShiftProvider,
        QuoteShiftProvider $quoteShiftProvider,
        ServiceShiftProvider $serviceShiftProvider
    ) {
        $this->entityManager = $entityManager;
        $this->options = $options;
        $this->optionsFacade = $optionsFacade;
        $this->invoiceShiftProvider = $invoiceShiftProvider;
        $this->clientShiftProvider = $clientShiftProvider;
        $this->feeShiftProvider = $feeShiftProvider;
        $this->paymentShiftProvider = $paymentShiftProvider;
        $this->quoteShiftProvider = $quoteShiftProvider;
        $this->serviceShiftProvider = $serviceShiftProvider;
    }

    public function shift(?\DateTimeInterface $currentDate): void
    {
        $difference = $this->getMonthDifference($currentDate);
        if (! $difference) {
            return;
        }

        $queries = [];
        $queries = array_merge($queries, $this->invoiceShiftProvider->get());
        $queries = array_merge($queries, $this->clientShiftProvider->get());
        $queries = array_merge($queries, $this->feeShiftProvider->get());
        $queries = array_merge($queries, $this->paymentShiftProvider->get());
        $queries = array_merge($queries, $this->quoteShiftProvider->get());
        $queries = array_merge($queries, $this->serviceShiftProvider->get());

        foreach ($queries as $query) {
            $this->entityManager->getConnection()->executeQuery(
                $query,
                [
                    'difference' => sprintf('\'+%d months\'', $difference),
                ]
            );

            $this->optionsFacade->updateGeneral(General::DEMO_MIGRATION_SHIFT, $difference);
        }
    }

    private function getMonthDifference(?\DateTimeInterface $currentDate): int
    {
        $base = DateTimeImmutableFactory::createDate(self::BASE_DATE);
        $now = $currentDate ?? new \DateTimeImmutable('midnight');
        if (! $now instanceof \DateTimeImmutable) {
            $now = DateTimeImmutableFactory::createFromInterface($now);
        }

        if ($now < $base) {
            throw new \RuntimeException('Does not work, when you\'re in the past. Travel back to the future!');
        }

        $difference = 0;
        $previousFormat = $now->format('Y-m');
        while ($now->format('Y-m') !== $base->format('Y-m')) {
            $now = $now->modify('-1 day');
            if ($now->format('Y-m') !== $previousFormat) {
                ++$difference;
            }
            $previousFormat = $now->format('Y-m');
        }

        // if the database was already shifted, when demo migration was created, we need to offset the base date shift
        $alreadyShiftedBy = (int) $this->options->getGeneral(General::DEMO_MIGRATION_SHIFT, 0);

        return $difference - $alreadyShiftedBy;
    }
}
