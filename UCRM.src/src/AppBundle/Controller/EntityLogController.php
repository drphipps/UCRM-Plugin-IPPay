<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Credit;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentStripe;
use AppBundle\Grid\EntityLog\EntityLogGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use AppBundle\Util\Strings;
use SchedulingBundle\Entity\Job;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/logs/entity")
 */
class EntityLogController extends BaseController
{
    /**
     * @Route("", name="entity_log_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="System log", path="System -> Logs -> System log")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(EntityLogGridFactory::class)->create(null);
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'entity_log/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="system_log_detail", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showSystemLogDetailsAction(EntityLog $entityLog): Response
    {
        $client = $entityLog->getClient();
        $entityLogData = [];

        if ($entityLog->getLog() !== null) {
            $log = unserialize($entityLog->getLog());

            switch ($entityLog->getChangeType()) {
                case EntityLog::EDIT:
                    foreach ($log as $key => $value) {
                        foreach ($value as $subKey => $subValue) {
                            if ($subValue instanceof \DateTime) {
                                $value[$subKey] = $this->get(Formatter::class)->formatDate($subValue);
                            } elseif ($subValue === null) {
                                $value[$subKey] = ' - ';
                            } elseif (is_array($subValue)) {
                                switch ($entityLog->getEntity()) {
                                    case Credit::class:
                                    case Payment::class:
                                    case PaymentStripe::class:
                                        if ($key == 'amount') {
                                            $subValue['logMsg']['message'] = $this->get(Formatter::class)->formatCurrency(
                                                $subValue['logMsg']['message'],
                                                $client ? $client->getCurrencyCode() : null,
                                                $client ? $client->getOrganization()->getLocale() : null
                                            );
                                        }
                                        break;
                                }
                                $value[$subKey] = $subValue['logMsg']['message'] ?? '';
                            } elseif (
                                $entityLog->getEntity() === Job::class
                                && $key === 'status'
                                && array_key_exists($subValue, Job::STATUSES)
                            ) {
                                $value[$subKey] = $this->trans(Job::STATUSES[$subValue]);
                            }
                        }
                        $entityLogData[Strings::humanize($key)] = $value;
                    }
                    break;
                default:
                    switch ($entityLog->getEntity()) {
                        case Credit::class:
                        case Payment::class:
                        case PaymentStripe::class:
                            $log['logMsg']['replacements'] = $this->get(Formatter::class)->formatCurrency(
                                $log['logMsg']['replacements'],
                                $client ? $client->getCurrencyCode() : null,
                                $client ? $client->getOrganization()->getLocale() : null
                            );
                            break;
                    }
                    $entityLogData = sprintf(
                        $this->trans($log['logMsg']['message']),
                        '"' . $log['logMsg']['replacements'] . '"'
                    );
                    break;
            }
        }

        $userName = '';
        if ($entityLog->getUser()) {
            $userName = implode(' ', [$entityLog->getUser()->getFirstName(), $entityLog->getUser()->getLastName()]);

            if (empty(trim($userName))) {
                $userName = $entityLog->getUser()->getUsername();
            }
        }

        return $this->render(
            'components/system_log_details_modal.html.twig',
            [
                'entityLog' => $entityLog,
                'entityLogData' => $entityLogData,
                'userName' => $userName,
            ]
        );
    }
}
