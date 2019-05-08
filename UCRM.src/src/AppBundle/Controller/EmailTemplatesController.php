<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\NotificationTemplateDataProvider;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Facade\NotificationTemplateFacade;
use AppBundle\Form\EmailTemplatesType;
use AppBundle\Handler\Notification\NotificationTemplatePreviewHandler;
use AppBundle\Security\Permission;
use AppBundle\Service\HtmlSanitizer;
use AppBundle\Service\Notification\DummyNotificationFactory;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/customization/email-templates")
 */
class EmailTemplatesController extends BaseController
{
    /**
     * @Route("", name="email_templates_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Email templates",
     *     path="System -> Customization -> Email templates",
     *     extra={
     *         "System notifications",
     *         "Draft created",
     *         "Invoice created",
     *         "Forgotten password",
     *         "Invitation email",
     *         "Billing",
     *         "Invoice near due date",
     *         "New invoice",
     *         "New proforma invoice",
     *         "New quote",
     *         "Invoice overdue",
     *         "Payment receipt",
     *         "Subscription amount changed",
     *         "Subscription cancelled",
     *         "Suspension",
     *         "Postpone suspend",
     *         "Service suspended",
     *         "Ticketing",
     *         "Ticket changed status",
     *         "Ticket commented by admin",
     *         "Ticket created by admin",
     *         "Ticket commented by admin (unknown client)",
     *         "Ticket commented by admin (with IMAP enabled)",
     *         "Automatic reply to new ticket",
     *     }
     * )
     */
    public function indexAction(Request $request): Response
    {
        $templates = $this->get(NotificationTemplateDataProvider::class)->getAllEmailTemplates();

        $form = $this->createForm(
            EmailTemplatesType::class,
            $templates
        );
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(NotificationTemplateFacade::class)->handleUpdateTemplates($templates);

            $this->addTranslatedFlash('success', 'Templates have been saved.');

            return $this->redirectToRoute('email_templates_index');
        }

        return $this->render(
            'email_templates/index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/preview/{id}", name="email_templates_preview", requirements={"id": "\d+"})
     * @Method({"POST"})
     * @Permission("edit")
     * @CsrfToken(methods={"POST"})
     */
    public function previewAction(Request $request, NotificationTemplate $notificationTemplate): Response
    {
        if ($body = $request->get('body')) {
            $notificationTemplate->setBody($body);
        }

        if ($subject = $request->get('subject')) {
            $notificationTemplate->setSubject($subject);
        }

        $notification = $this->get(DummyNotificationFactory::class)->create($notificationTemplate);

        $previewHandler = $this->get(NotificationTemplatePreviewHandler::class);
        $previewHandler->setTemplateBody($notification->getBodyTemplate());
        $previewHandler->setExtraCss($notification->getExtraCss());

        return $this->render(
            'email_templates/components/template_preview.html.twig',
            [
                'subject' => $notification->getSubject(),
                'typeForView' => NotificationTemplate::NOTIFICATION_TYPES[$notificationTemplate->getType()],
            ]
        );
    }

    /**
     * @Route("/preview-email-body", name="email_templates_preview_email")
     * @Method({"GET"})
     * @Permission("edit")
     * @CsrfToken()
     */
    public function previewEmailBodyAction(): Response
    {
        $previewHandler = $this->get(NotificationTemplatePreviewHandler::class);
        $response = $this->render(
            'email_templates/components/template_preview_email_body.html.twig',
            [
                'mailBody' => $this->get(HtmlSanitizer::class)
                    ->sanitize($previewHandler->getTemplateBody() ?: ''),
                'extraCss' => $previewHandler->getExtraCss(),
            ]
        );

        $response->headers->set('Content-Security-Policy', 'script-src \'self\'');

        return $response;
    }
}
