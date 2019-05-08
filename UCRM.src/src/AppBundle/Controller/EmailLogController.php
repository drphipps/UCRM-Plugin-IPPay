<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\EmailLog;
use AppBundle\Exception\EmailAttachmentNotFoundException;
use AppBundle\Facade\EmailLogFacade;
use AppBundle\Form\Data\EmailResendData;
use AppBundle\Form\EmailResendType;
use AppBundle\Grid\EmailLog\EmailLogGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\EmailLog\EmailLogRenderer;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/logs/email")
 */
class EmailLogController extends BaseController
{
    /**
     * @Route("", name="email_log_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Email log", path="System -> Logs -> Email log")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(EmailLogGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'email_log/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="email_log_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(EmailLog $emailLog): Response
    {
        $renderer = $this->get(EmailLogRenderer::class);

        return $this->render(
            'email_log/show.html.twig',
            [
                'emailLog' => $emailLog,
                'emailLogMessage' => $renderer->renderMessage($emailLog, false, false),
                'emailLogRecipient' => $renderer->renderRecipient($emailLog),
            ]
        );
    }

    /**
     * @Route("/resend/{id}", name="email_log_resend")
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function resendAction(EmailLog $emailLog, Request $request): Response
    {
        if (empty(trim($emailLog->getRecipient()))) {
            $this->addTranslatedFlash('error', 'Email could not be sent, because recipient is empty.');
        } else {
            try {
                $this->get(EmailLogFacade::class)->resendEmail($emailLog);
                $this->addTranslatedFlash('success', 'Email has been added to the send queue.');
            } catch (EmailAttachmentNotFoundException $exception) {
                $this->addTranslatedFlash('error', 'Email can\'t be resent because the original attachment is missing.');
            } catch (\Exception $exception) {
                $this->addTranslatedFlash('error', 'Email could not be added to the send queue!');
                $this->addTranslatedFlash('warning', $exception->getMessage());
            }
        }

        if ($id = $request->get('mailing')) {
            return $this->redirectToRoute('mailing_show', ['id' => $id]);
        }

        return $this->redirectToRoute('email_log_index');
    }

    /**
     * @Route("/resend-failed", name="email_log_resend_failed")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function resendFailedModalAction(Request $request)
    {
        $resendData = new EmailResendData();
        $resendData->resendSince = new \DateTime();
        $form = $this->createForm(
            EmailResendType::class,
            $resendData,
            [
                'action' => $this->generateUrl('email_log_resend_failed'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resentMails = $this->get(EmailLogFacade::class)->resendFailedEmailsSince($resendData->resendSince);
            if ($resentMails > 0) {
                $this->addTranslatedFlash(
                    'success',
                    '%count% emails have been added to the send queue.',
                    $resentMails
                );
            } else {
                $this->addTranslatedFlash(
                    'success',
                    'No failed emails have been found for the given period, nothing to resend.',
                    $resentMails
                );
            }

            return $this->createAjaxRedirectResponse('email_log_index');
        }

        return $this->render(
            'email_log/resend_failed.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
