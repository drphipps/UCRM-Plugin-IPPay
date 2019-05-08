<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Sandbox\SandboxTerminator;
use AppBundle\DataProvider\SandboxDataProvider;
use AppBundle\Exception\ResetException;
use AppBundle\Form\Data\SandboxTerminationData;
use AppBundle\Form\SandboxTerminationType;
use AppBundle\Security\Permission;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/sandbox-termination")
 */
class SandboxTerminationController extends BaseController
{
    /**
     * @Route("", name="sandbox")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function indexAction(Request $request): Response
    {
        if (! $this->isSandbox() || Helpers::isDemo()) {
            $this->addTranslatedFlash('error', 'The system is not in demo mode.');

            return $this->redirectToRoute('homepage');
        }

        $sandboxTermination = new SandboxTerminationData();
        $formSmartReset = $this->createForm(SandboxTerminationType::class, $sandboxTermination);
        $formSmartReset->handleRequest($request);
        if ($formSmartReset->isSubmitted() && $formSmartReset->isValid()) {
            if (! $sandboxTermination->keepClients) {
                $sandboxTermination->resetInvitationEmails = false;
                $sandboxTermination->keepServices = false;
                $sandboxTermination->keepInvoices = false;
                $sandboxTermination->resetInvoiceEmails = false;
                $sandboxTermination->keepPayments = false;
                $sandboxTermination->keepTickets = false;
            }

            if (! $sandboxTermination->keepServices) {
                $sandboxTermination->keepInvoices = false;
                $sandboxTermination->resetInvoiceEmails = false;
            }

            try {
                if ($this->get(SandboxTerminator::class)->requestTermination($sandboxTermination)) {
                    $this->addTranslatedFlash(
                        'success',
                        'Sandbox mode termination is queued and will be processed in several minutes.'
                    );

                    return $this->redirectToRoute('homepage');
                }
            } catch (ResetException $resetException) {
                $this->addTranslatedFlash(
                    'success',
                    'Sandbox mode termination is queued and will be processed in several minutes.'
                );

                return $this->redirectToRoute('sandbox');
            }
        }

        return $this->render(
            'sandbox/index.html.twig',
            [
                'form' => $formSmartReset->createView(),
                'stats' => $this->get(SandboxDataProvider::class)->getOverview(),
            ]
        );
    }

    /**
     * @Route("/reset-all", name="sandbox_reset_all")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function resetAllAction(): Response
    {
        if (! $this->isSandbox() || Helpers::isDemo()) {
            $this->addTranslatedFlash('error', 'The system is not in demo mode.');

            return $this->redirectToRoute('homepage');
        }

        try {
            $sandboxTermination = new SandboxTerminationData();
            $sandboxTermination->mode = SandboxTerminationData::MODE_ALL;

            if ($this->get(SandboxTerminator::class)->requestTermination($sandboxTermination)) {
                $this->addTranslatedFlash(
                    'success',
                    'Factory reset is queued and will be processed in several minutes.'
                );

                return $this->redirectToRoute('homepage');
            }
        } catch (ResetException $resetException) {
            $this->addTranslatedFlash(
                'success',
                'Factory reset is queued and will be processed in several minutes.'
            );
        }

        return $this->redirectToRoute('sandbox');
    }
}
