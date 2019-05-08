<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\EntityLog;

use AppBundle\Entity\Credit;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentStripe;
use AppBundle\Util\Formatter;
use AppBundle\Util\Strings;
use Symfony\Component\Translation\TranslatorInterface;

class EntityLogRenderer
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(TranslatorInterface $translator, Formatter $formatter)
    {
        $this->translator = $translator;
        $this->formatter = $formatter;
    }

    public function renderMessage(EntityLog $entityLog): string
    {
        switch ($entityLog->getChangeType()) {
            case EntityLog::EDIT:
                $entityName = substr($entityLog->getEntity(), strrpos($entityLog->getEntity(), '\\') + 1);
                $message = sprintf(
                    '%s %s',
                    $this->translator->trans(Strings::humanize($entityName)),
                    $this->translator->trans('edited')
                );
                break;
            default:
                $log = $entityLog->getLog();
                if ($log === null) {
                    return '';
                }

                $log = unserialize($entityLog->getLog(), ['allowed_classes' => false]);
                switch ($entityLog->getEntity()) {
                    case Credit::class:
                    case Payment::class:
                    case PaymentStripe::class:
                        $log['logMsg']['replacements'] = $this->formatter->formatCurrency(
                            $log['logMsg']['replacements'],
                            $entityLog->getClient() ? $entityLog->getClient()->getCurrencyCode() : null,
                            $entityLog->getClient() ? $entityLog->getClient()->getOrganization()->getLocale() : null
                        );
                        break;
                }
                $message = sprintf(
                    $this->translator->trans($log['logMsg']['message']),
                    '"' . $log['logMsg']['replacements'] . '"'
                );
        }

        return $message;
    }

    public function renderClientViewLogDetails(EntityLog $entityLog): string
    {
        $log = $entityLog->getLog();
        if ($log === null) {
            return '';
        }
        $log = unserialize($log, ['allowed_classes' => false]);

        if ($log['logMsg'] ?? false) {
            return '';
        }

        return json_encode($log);
    }
}
