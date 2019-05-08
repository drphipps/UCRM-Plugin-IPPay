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
use AppBundle\Form\SuspensionTemplatesType;
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
 * @Route("/system/customization/suspension-templates")
 */
class SuspensionTemplatesController extends BaseController
{
    /**
     * @Route("", name="suspension_templates_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Suspension templates",
     *     path="System -> Customization -> Suspension templates",
     *     extra={
     *         "Suspend anonymous",
     *         "Suspend prepared",
     *         "Suspend recognized",
     *         "Suspend terminated",
     *         "Suspension for manually stopped services",
     *     }
     * )
     */
    public function indexAction(Request $request): Response
    {
        $templates = $this->get(NotificationTemplateDataProvider::class)->getAllSuspensionTemplates();

        $form = $this->createForm(
            SuspensionTemplatesType::class,
            [
                'templates' => $templates,
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(NotificationTemplateFacade::class)->handleUpdateTemplates($templates);

            $this->addTranslatedFlash('success', 'Templates have been saved.');

            return $this->redirectToRoute('suspension_templates_index');
        }

        return $this->render(
            'suspension_templates/index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/preview/{id}", name="suspension_templates_preview", requirements={"id": "\d+"})
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

        $this->get(NotificationTemplatePreviewHandler::class)->setTemplateBody($notification->getBodyTemplate());
        $this->get(NotificationTemplatePreviewHandler::class)->setTemplateHeading($notification->getSubject());

        return $this->render(
            'suspension_templates/components/template_preview.html.twig',
            [
                'typeForView' => NotificationTemplate::NOTIFICATION_TYPES[$notificationTemplate->getType()],
            ]
        );
    }

    /**
     * @Route("/preview-body", name="suspension_templates_preview_body")
     * @Method({"GET"})
     * @Permission("edit")
     * @CsrfToken()
     */
    public function previewBodyAction(): Response
    {
        $response = $this->render(
            'suspension_templates/components/template_preview_email_body.html.twig',
            [
                'heading' => $this->get(HtmlSanitizer::class)
                    ->sanitize($this->get(NotificationTemplatePreviewHandler::class)->getTemplateHeading() ?: ''),
                'body' => $this->get(HtmlSanitizer::class)
                    ->sanitize($this->get(NotificationTemplatePreviewHandler::class)->getTemplateBody() ?: ''),
            ]
        );

        $response->headers->set('Content-Security-Policy', 'script-src \'self\'');

        return $response;
    }
}
