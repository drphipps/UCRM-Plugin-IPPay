<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\MailingDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Mailing;
use AppBundle\Facade\MailingFacade;
use AppBundle\Form\Data\MailingComposeMessageData;
use AppBundle\Form\Data\MailingFilterData;
use AppBundle\Form\Data\MailingPreviewData;
use AppBundle\Form\MailingComposeMessageType;
use AppBundle\Form\MailingFilterType;
use AppBundle\Form\MailingPreviewType;
use AppBundle\Grid\EmailLog\EmailLogGridFactory;
use AppBundle\Grid\Mailing\MailingGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Util\Helpers;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @Route("/system/tools/mailing")
 */
class MailingController extends BaseController
{
    private const MAILING_FILTER = 'mailingFilter';
    private const MAILING_RECIPIENTS = 'mailingRecipients';
    private const MAILING_MESSAGE = 'mailingMessage';

    /**
     * @Route("", name="mailing_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Mailing", path="System -> Tools -> Mailing")
     */
    public function indexAction(Request $request): Response
    {
        $countClientsWithoutInvitationEmail = $this->em->getRepository(Client::class)
            ->getCountClientsWithoutInvitationEmail();

        if (! $this->em->getRepository(Mailing::class)->existsAny()) {
            return $this->render(
                'mailing/empty.html.twig',
                [
                    'countClientsWithoutInvitationEmail' => $countClientsWithoutInvitationEmail,
                ]
            );
        }

        $grid = $this->get(MailingGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'mailing/index.html.twig',
            [
                'grid' => $grid,
                'countClientsWithoutInvitationEmail' => $countClientsWithoutInvitationEmail,
            ]
        );
    }

    /**
     * @Route("/new", name="mailing_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $session = $this->get(Session::class);
        $mailingFilterData = new MailingFilterData();
        $filterData = $session->get(self::MAILING_FILTER);

        if ($filterData && ! empty($filterData)) {
            $mailingFilterData = $this->get(MailingDataProvider::class)->getFilterDataEntities(
                $mailingFilterData,
                $filterData
            );
        }

        $form = $this->createForm(
            MailingFilterType::class,
            $mailingFilterData
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $idAccessor = function ($item) {
                return $item->getId();
            };

            $mailingFilter = [
                'organization' => $mailingFilterData->filterOrganizations->map($idAccessor)->toArray(),
                'clientType' => $mailingFilterData->filterClientTypes,
                'clientTag' => $mailingFilterData->filterClientTags->map($idAccessor)->toArray(),
                'servicePlan' => $mailingFilterData->filterServicePlans->map($idAccessor)->toArray(),
                'periodStartDay' => $mailingFilterData->filterPeriodStartDays,
                'site' => $mailingFilterData->filterSites->map($idAccessor)->toArray(),
                'includeLeads' => $mailingFilterData->filterIncludeLeads,
                'device' => $mailingFilterData->filterDevices->map($idAccessor)->toArray(),
            ];

            if ($session->get(self::MAILING_FILTER) !== $mailingFilter) {
                $session->remove(self::MAILING_RECIPIENTS);
            }

            $session->set(self::MAILING_FILTER, $mailingFilter);

            return $this->redirectToRoute('mailing_new_preview');
        }

        return $this->render(
            'mailing/new_filter.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/new/preview", name="mailing_new_preview")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function newPreviewAction(Request $request): Response
    {
        $session = $this->get(Session::class);
        $filters = $session->get(self::MAILING_FILTER);
        if (! $filters) {
            return $this->redirectWhenFilterIsMissing();
        }

        $sessionRecipients = $session->get(self::MAILING_RECIPIENTS);
        $clients = $this->get(MailingDataProvider::class)->getMailingPreviewData($filters, $sessionRecipients);

        $sendCheckboxes = new MailingPreviewData();

        // create checkbox and set checked for any clients if no session exists
        // or create unchecked checkboxes for any clients with first foreach
        // and set checked for clients from session with second foreach
        foreach ($clients as $client) {
            $sendCheckboxes->send[$client->getId()] = $sessionRecipients === null;
        }
        foreach ($sessionRecipients ?? [] as $clientId) {
            $sendCheckboxes->send[$clientId] = true;
        }

        $form = $this->createForm(
            MailingPreviewType::class,
            $sendCheckboxes,
            [
                'action' => $this->generateUrl('mailing_new_preview'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $clientIds = array_keys(array_filter($sendCheckboxes->send));

            if ($this->isBackButtonClicked($form)) {
                $session->set(self::MAILING_RECIPIENTS, $clientIds);

                return $this->redirectToRoute('mailing_new');
            }
            if ($form->isValid() && count($clientIds) > 0) {
                $session->set(self::MAILING_RECIPIENTS, $clientIds);

                return $this->redirectToRoute('mailing_new_message');
            }
            $this->addTranslatedFlash('warning', 'No recipients selected');
        }

        return $this->render(
            'mailing/new_preview.html.twig',
            [
                'clients' => $clients,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/new/message", name="mailing_new_message")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newMessageAction(Request $request): Response
    {
        $session = $this->get(Session::class);

        $filters = $session->get(self::MAILING_FILTER);
        if (! $filters) {
            return $this->redirectWhenFilterIsMissing();
        }

        $recipientIds = $session->get(self::MAILING_RECIPIENTS);
        if (! $recipientIds) {
            $this->addTranslatedFlash('warning', 'No recipients selected');

            return $this->redirectToRoute('mailing_new_preview');
        }

        $composeMessage = $session->get(self::MAILING_MESSAGE) ?: new MailingComposeMessageData();

        $form = $this->createForm(
            MailingComposeMessageType::class,
            $composeMessage
        );

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($this->isBackButtonClicked($form)) {
                $session->set(self::MAILING_MESSAGE, $composeMessage);

                return $this->redirectToRoute('mailing_new_preview');
            }
            if ($form->isValid()) {
                if (Helpers::isDemo()) {
                    $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

                    return $this->redirectToRoute('client_index');
                }

                $clients = $this->get(MailingDataProvider::class)->getClients($recipientIds);

                $mailing = new Mailing();

                $this->get(MailingFacade::class)->handleSendEmail($mailing, $filters, $clients, $composeMessage);

                $session->remove(self::MAILING_FILTER);
                $session->remove(self::MAILING_RECIPIENTS);
                $session->remove(self::MAILING_MESSAGE);

                $this->addTranslatedFlash('success', 'Mailing has been added to the send queue');

                return $this->redirectToRoute('mailing_index');
            }
        }

        return $this->render(
            'mailing/new_message.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="mailing_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(Request $request, Mailing $mailing): Response
    {
        $grid = $this->get(EmailLogGridFactory::class)->create(null, null, $mailing);
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'mailing/show.html.twig',
            [
                'mailing' => $mailing,
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new/cancel", name="mailing_cancel")
     * @Method("GET")
     * @Permission("view")
     */
    public function handleCancelAction(): Response
    {
        $session = $this->get(Session::class);
        $session->remove(self::MAILING_FILTER);
        $session->remove(self::MAILING_RECIPIENTS);
        $session->remove(self::MAILING_MESSAGE);

        return $this->redirectToRoute('mailing_index');
    }

    private function isBackButtonClicked(FormInterface $form): bool
    {
        $button = $form->get('back');

        assert($button instanceof ClickableInterface);

        return $button->isClicked();
    }

    private function redirectWhenFilterIsMissing(): Response
    {
        $this->addTranslatedFlash('warning', 'Wrong filter format. Try again.');

        return $this->redirectToRoute('mailing_new');
    }
}
