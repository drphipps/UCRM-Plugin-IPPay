<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Component\Grid\Filter\DateFilterField;
use AppBundle\Component\Grid\Filter\SelectFilterField;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Organization;
use AppBundle\Grid\Invoice\InvoicedRevenueGridFactory;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/reports/billing/invoiced-revenue")
 */
class InvoicedRevenueController extends BaseController
{
    const ALL_INVOICES = 'all-invoices';
    const UNPAID_INVOICES = 'unpaid-invoices';
    const OVERDUE_INVOICES = 'overdue-invoices';
    const PAID_INVOICES = 'paid-invoices';

    /**
     * @Route("/{filterType}", name="invoiced_revenue_index", defaults={"filterType" = null})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function indexAction(string $filterType = self::ALL_INVOICES): Response
    {
        $grid = $this->get(InvoicedRevenueGridFactory::class)->create($filterType);
        if ($response = $grid->processMultiAction()) {
            return $response;
        }

        /** @var DateFilterField $filterDate */
        $filterDate = $grid->getFilter(InvoicedRevenueGridFactory::FILTER_DATE);
        $rangeFrom = $grid->getActiveFilter(InvoicedRevenueGridFactory::FILTER_DATE_FROM);
        $rangeTo = $grid->getActiveFilter(InvoicedRevenueGridFactory::FILTER_DATE_TO);

        try {
            /** @var SelectFilterField $filterOrganization */
            $filterOrganization = $grid->getFilter(InvoicedRevenueGridFactory::FILTER_ORGANIZATION);
            $organization = $this->em->find(
                Organization::class,
                (int) $grid->getActiveFilter(InvoicedRevenueGridFactory::FILTER_ORGANIZATION)
            );
        } catch (\InvalidArgumentException $e) {
            $organization = null;
        }

        if (! $organization) {
            $organization = $this->em->getRepository(Organization::class)->getFirstSelected();
            if (! $organization) {
                throw $this->createNotFoundException();
            }

            if (isset($filterOrganization)) {
                $filterOrganization->setDefaultValue($organization->getId());
            }
        }

        if (empty($rangeFrom)) {
            $from = new \DateTimeImmutable('first day of this month');
            $filterDate->setRangeFrom($from->format('Y-m-d'));
        } else {
            try {
                $from = new \DateTimeImmutable($rangeFrom);
            } catch (\Exception $e) {
                $from = new \DateTimeImmutable('first day of this month');
                $filterDate->setRangeFrom($from->format('Y-m-d'));
            }
        }

        if (empty($rangeTo)) {
            $to = new \DateTimeImmutable('last day of this month');
            $filterDate->setRangeTo($to->format('Y-m-d'));
        } else {
            try {
                $to = new \DateTimeImmutable($rangeTo);
            } catch (\Exception $e) {
                $to = new \DateTimeImmutable('last day of this month');
                $filterDate->setRangeTo($to->format('Y-m-d'));
            }
        }
        $filterDate->setDefaultValue(true);

        $total = $this->em->getRepository(Invoice::class)->getInvoicesTotalSum($from, $to, $organization);
        $unpaid = $this->em->getRepository(Invoice::class)->getInvoicesUnpaidSum($from, $to, $organization);
        $overdue = $this->em->getRepository(Invoice::class)->getInvoicesOverdueSum($from, $to, $organization);
        $paid = $this->em->getRepository(Invoice::class)->getInvoicesPaidSum($from, $to, $organization);
        $totalTax = $this->em->getRepository(Invoice::class)->getInvoicesTotalTax($from, $to, $organization);
        $totalTaxed = $this->em->getRepository(Invoice::class)->getInvoicesTotalTaxed($from, $to, $organization);

        return $this->render(
            'invoiced_revenue/index.html.twig',
            [
                'allInvoices' => self::ALL_INVOICES,
                'currencyCode' => $organization->getCurrency()->getCode(),
                'filterType' => $filterType,
                'from' => $from->format('Y-m-d'),
                'gridInvoices' => $grid,
                'organization' => $organization->getId(),
                'overdue' => $overdue,
                'overdueInvoices' => self::OVERDUE_INVOICES,
                'paid' => $paid,
                'paidInvoices' => self::PAID_INVOICES,
                'to' => $to->format('Y-m-d'),
                'total' => $total,
                'totalTaxed' => $totalTaxed,
                'totalTax' => $totalTax,
                'unpaid' => $unpaid,
                'unpaidInvoices' => self::UNPAID_INVOICES,
            ]
        );
    }
}
