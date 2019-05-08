<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Util\DateTimeImmutableFactory;
use AppBundle\Util\Formatter;
use Nette\Utils\Html;
use Symfony\Component\Translation\TranslatorInterface;

class InvoiceDueDateRenderer
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Formatter $formatter, TranslatorInterface $translator)
    {
        $this->formatter = $formatter;
        $this->translator = $translator;
    }

    public function renderDueDate(Invoice $invoice): string
    {
        $now = new \DateTimeImmutable('midnight');
        $due = DateTimeImmutableFactory::createFromInterface($invoice->getDueDate());
        $due = $due->modify('midnight');
        $diff = $now->diff($due);

        $date = $this->formatter->formatDate(
            $invoice->getDueDate(),
            Formatter::DEFAULT,
            Formatter::NONE
        );
        if ($invoice->isOverdue() || ! $diff->invert) {
            $tooltip = $date;
            $date = $this->translator->transChoice(
                $invoice->isOverdue()
                    ? 'overdue for %count% days'
                    : 'due in %count% days',
                $diff->days,
                [
                    '%count%' => $diff->days,
                ]
            );
        }

        $span = Html::el('span');
        $span->addText($date);
        if ($invoice->isOverdue()) {
            $span->setAttribute('class', 'invoice--overdue');
        }

        if (isset($tooltip)) {
            $span->setAttribute('data-tooltip', htmlspecialchars($tooltip ?? '', ENT_QUOTES));
        }

        return (string) $span;
    }
}
