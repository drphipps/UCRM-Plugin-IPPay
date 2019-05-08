<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use Nette\Utils\Html;
use Symfony\Component\Translation\TranslatorInterface;

class BadgeFactory
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function createInvoiceStatusBadge(int $status, bool $draftHr = true): string
    {
        if ($draftHr && $status === Invoice::DRAFT) {
            return (string) Html::el('hr');
        }

        $classes = [
            'invoice-status-badge',
            'ml-10',
            'appType--micro',
        ];

        switch ($status) {
            case Invoice::UNPAID:
                $classes[] = 'primary';
                break;
            case Invoice::PARTIAL:
                $classes[] = 'success';
                break;
            case Invoice::PROFORMA_PROCESSED:
            case Invoice::PAID:
            case Invoice::VOID:
                $classes[] = 'appType--quiet';
                break;
            case Invoice::DRAFT:
                $classes[] = 'danger';
                break;
        }

        $text = $this->translator->trans(Invoice::STATUS_REPLACE_STRING[$status]);

        $el = Html::el(
            'span',
            [
                'class' => $classes,
            ]
        );

        return (string) $el->setText($text);
    }

    public function createInvoiceUncollectibleBadge(Invoice $invoice): string
    {
        if (! $invoice->isUncollectible()) {
            return '';
        }

        $el = Html::el(
            'span',
            [
                'class' => [
                    'invoice-status-badge',
                    'ml-10',
                    'appType--micro',
                    'color--darker',
                ],
            ]
        );

        return (string) $el->setText($this->translator->trans('Uncollectible'));
    }

    public function createQuoteStatusBadge(int $status): string
    {
        $classes = [
            'invoice-status-badge',
            'ml-10',
            'appType--micro',
        ];
        switch ($status) {
            case Quote::STATUS_OPEN:
                $classes[] = 'primary';
                break;
            case Quote::STATUS_ACCEPTED:
                $classes[] = 'success';
                break;
            case Quote::STATUS_REJECTED:
                $classes[] = 'appType--quiet';
                break;
        }

        $el = Html::el(
            'span',
            [
                'class' => $classes,
            ]
        );

        return (string) $el->setText($this->translator->trans(Quote::STATUSES[$status]));
    }
}
