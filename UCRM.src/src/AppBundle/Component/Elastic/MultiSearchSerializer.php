<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic;

use AppBundle\Entity\Client;
use AppBundle\Entity\Device;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Payment;
use AppBundle\Util\Formatter;
use Elastica\Result;
use FOS\ElasticaBundle\HybridResult;
use Nette\Utils\Strings;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TicketingBundle\Entity\Ticket;

class MultiSearchSerializer
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var SearchUtilities
     */
    private $searchUtilities;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(
        TranslatorInterface $translator,
        RouterInterface $router,
        Formatter $formatter,
        SearchUtilities $searchUtilities,
        bool $debug
    ) {
        $this->translator = $translator;
        $this->router = $router;
        $this->formatter = $formatter;
        $this->searchUtilities = $searchUtilities;
        $this->debug = $debug;
    }

    /**
     * @todo This needs to be refactored with DI Resolver.
     */
    public function serializeResults(array $multiResults): array
    {
        $categorized = [];
        /** @var Result[]|HybridResult[] $results */
        foreach ($multiResults as $type => $results) {
            $categorized[$type] = [
                'category' => $type,
                'categoryLabel' => $this->translator->trans(MultiSearch::CATEGORIES[$type]),
                'items' => [],
            ];

            foreach ($results as $result) {
                $serializedResult = null;
                switch ($type) {
                    case MultiSearch::TYPE_CLIENT:
                        assert($result instanceof HybridResult);
                        $serializedResult = $this->serializeClient($result);
                        break;
                    case MultiSearch::TYPE_DEVICE:
                        assert($result instanceof HybridResult);
                        $serializedResult = $this->serializeDevice($result);
                        break;
                    case MultiSearch::TYPE_INVOICE:
                        assert($result instanceof HybridResult);
                        $serializedResult = $this->serializeInvoice($result);
                        break;
                    case MultiSearch::TYPE_QUOTE:
                        assert($result instanceof HybridResult);
                        $serializedResult = $this->serializeQuote($result);
                        break;
                    case MultiSearch::TYPE_PAYMENT:
                        assert($result instanceof HybridResult);
                        $serializedResult = $this->serializePayment($result);
                        break;
                    case MultiSearch::TYPE_TICKET:
                        assert($result instanceof HybridResult);
                        $categorized[$type]['items'][] = $this->serializeTicket($result);
                        break;
                    case MultiSearch::TYPE_HELP:
                        assert($result instanceof Result);
                        $serializedResult = [
                            'id' => $result->getId(),
                            'label' => $result->getData()['helpName'] ?? null,
                            'category' => MultiSearch::TYPE_HELP,
                            'url' => $this->router->generate(
                                'help_index',
                                [
                                    'section' => $result->getId(),
                                ]
                            ),
                        ];
                        break;
                    case MultiSearch::TYPE_NAVIGATION:
                        assert($result instanceof Result);
                        $serializedResult = [
                            'id' => $result->getId(),
                            'label' => $result->getData()['heading'] ?? null,
                            'description' => $result->getData()['path'] ?? null,
                            'category' => MultiSearch::TYPE_NAVIGATION,
                            'url' => $this->router->generate($result->getId()),
                        ];
                        break;
                }

                if ($serializedResult) {
                    if ($this->debug) {
                        $serializedResult['score'] = $result instanceof HybridResult
                            ? $result->getResult()->getScore()
                            : $result->getScore();
                    }
                    $categorized[$type]['items'][] = $serializedResult;
                }
            }

            $sortCallback = null;
            switch ($type) {
                case MultiSearch::TYPE_INVOICE:
                    $sortCallback = function ($a, $b) {
                        return $this->sortInvoices($a, $b);
                    };
                    break;
                case MultiSearch::TYPE_PAYMENT:
                    $sortCallback = function ($a, $b) {
                        return $this->sortPayments($a, $b);
                    };
                    break;
            }

            if ($sortCallback) {
                usort($categorized[$type]['items'], $sortCallback);
            }

            $categorized[$type]['items'] = array_map(
                function ($item) {
                    return array_filter(
                        $item,
                        function ($key) {
                            return ! Strings::startsWith($key, '_');
                        },
                        ARRAY_FILTER_USE_KEY
                    );
                },
                $categorized[$type]['items']
            );
        }

        return $categorized;
    }

    private function sortInvoices(array $a, array $b): int
    {
        if ($a['_score'] !== $b['_score']) {
            return $b['_score'] <=> $a['_score'];
        }

        if ($a['_invoiceNumber'] !== $b['_invoiceNumber']) {
            return $b['_invoiceNumber'] <=> $a['_invoiceNumber'];
        }

        return $b['_id'] <=> $a['_id'];
    }

    private function sortPayments(array $a, array $b): int
    {
        if ($a['_score'] !== $b['_score']) {
            return $b['_score'] <=> $a['_score'];
        }

        if ($a['_createdDate'] !== $b['_createdDate']) {
            return $b['_createdDate'] <=> $a['_createdDate'];
        }

        return $b['_id'] <=> $a['_id'];
    }

    private function serializeClient(HybridResult $result): array
    {
        /** @var Client $client */
        $client = $result->getTransformed();

        return [
            'label' => $client->getNameForView(),
            'description' => $this->searchUtilities->getClientDescription($client),
            'url' => $this->router->generate(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            ),
        ];
    }

    private function serializeDevice(HybridResult $result): array
    {
        /** @var Device $device */
        $device = $result->getTransformed();

        $description = [
            $device->getVendor()->getName(),
            $device->getModelName(),
        ];

        return [
            'label' => $device->getNameWithSite(),
            'description' => implode(' / ', array_filter($description)),
            'url' => $this->router->generate(
                'device_show',
                [
                    'id' => $device->getId(),
                ]
            ),
        ];
    }

    private function serializeInvoice(HybridResult $result): array
    {
        /** @var Invoice $invoice */
        $invoice = $result->getTransformed();

        return [
            '_id' => $invoice->getId(),
            '_score' => round($result->getResult()->getScore(), 3),
            '_invoiceNumber' => $invoice->getInvoiceNumber(),
            'label' => sprintf(
                '%s - %s',
                $invoice->getInvoiceNumber() ?: $this->translator->trans('draft'),
                $invoice->getClient()->getNameForView()
            ),
            'description' => sprintf(
                '%s / %s: %s / %s: %s',
                $this->translator->trans($invoice->getInvoiceStatusName()),
                $this->translator->trans('Due'),
                $this->formatter->formatDate($invoice->getDueDate(), Formatter::DEFAULT, Formatter::NONE),
                $this->translator->trans('Amount due'),
                $this->formatter->formatCurrency(
                    $invoice->getAmountToPay(),
                    $invoice->getCurrency()->getCode(),
                    $invoice->getOrganization()->getLocale()
                )
            ),
            'url' => $this->router->generate(
                'client_invoice_show',
                [
                    'id' => $invoice->getId(),
                ]
            ),
        ];
    }

    private function serializeQuote(HybridResult $result): array
    {
        /** @var Quote $quote */
        $quote = $result->getTransformed();

        return [
            '_id' => $quote->getId(),
            '_score' => round($result->getResult()->getScore(), 3),
            '_quoteNumber' => $quote->getQuoteNumber(),
            'label' => sprintf(
                '%s - %s',
                $quote->getQuoteNumber(),
                $quote->getClient()->getNameForView()
            ),
            'description' => sprintf(
                '%s: %s / %s: %s',
                $this->translator->trans('Created'),
                $this->formatter->formatDate($quote->getCreatedDate(), Formatter::DEFAULT, Formatter::NONE),
                $this->translator->trans('Total'),
                $this->formatter->formatCurrency(
                    $quote->getTotal(),
                    $quote->getCurrency()->getCode(),
                    $quote->getOrganization()->getLocale()
                )
            ),
            'url' => $this->router->generate(
                'client_quote_show',
                [
                    'id' => $quote->getId(),
                ]
            ),
        ];
    }

    private function serializePayment(HybridResult $result): array
    {
        /** @var Payment $payment */
        $payment = $result->getTransformed();

        return [
            '_id' => $payment->getId(),
            '_score' => round($result->getResult()->getScore(), 3),
            '_createdDate' => $payment->getCreatedDate(),
            'label' => sprintf(
                '%s - %s',
                $this->formatter->formatCurrency(
                    $payment->getAmount(),
                    $payment->getCurrency() ? $payment->getCurrency()->getCode() : null,
                    $payment->getClient() ? $payment->getClient()->getOrganization()->getLocale() : null
                ),
                $payment->getClient() ? $payment->getClient()->getNameForView() : 'unmatched'
            ),
            'description' => sprintf(
                '%s: %s / %s',
                $this->translator->trans('Created'),
                $this->formatter->formatDate($payment->getCreatedDate(), Formatter::DEFAULT, Formatter::NONE),
                $this->translator->trans($payment->getMethodName())
            ),
            'url' => $this->router->generate(
                'payment_show',
                [
                    'id' => $payment->getId(),
                ]
            ),
        ];
    }

    private function serializeTicket(HybridResult $result): array
    {
        /** @var Ticket $ticket */
        $ticket = $result->getTransformed();

        return [
            '_id' => $ticket->getId(),
            '_score' => round($result->getResult()->getScore(), 3),
            '_createdDate' => $ticket->getCreatedAt(),
            'label' => sprintf(
                '#%s - %s',
                $ticket->getId(),
                $ticket->getSubject()
            ),
            'description' => sprintf(
                '%s: %s / %s: %s / %s: %s / %s: %s',
                $this->translator->trans('Created'),
                $this->formatter->formatDate($ticket->getCreatedAt(), Formatter::MEDIUM, Formatter::NONE),
                $this->translator->trans('Status'),
                $this->translator->trans(Ticket::STATUSES[$ticket->getStatus()]),
                $this->translator->trans('Assigned user'),
                $ticket->getAssignedUser()
                    ? $ticket->getAssignedUser()->getNameForView()
                    : $this->translator->trans('Unassigned'),
                $this->translator->trans('Client'),
                $ticket->getClient()
                    ? $ticket->getClient()->getNameForView()
                    : $this->translator->trans('Unassigned')
            ),
            'url' => $this->router->generate(
                'ticketing_index',
                [
                    'ticketId' => $ticket->getId(),
                ]
            ),
        ];
    }
}
