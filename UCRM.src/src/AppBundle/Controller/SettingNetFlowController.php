<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\NetflowExcludedIp;
use AppBundle\Entity\Option;
use AppBundle\Facade\NetflowExcludedIpFacade;
use AppBundle\Form\Data\Settings\NetFlowOptionsData;
use AppBundle\Form\NetflowExcludedIpType;
use AppBundle\Form\SettingNetFlowOptionsType;
use AppBundle\Grid\Settings\NetFlowExcludedIpGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\OptionsManager;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/netflow")
 * @PermissionControllerName(SettingController::class)
 */
class SettingNetFlowController extends BaseController
{
    /**
     * @Route("", name="setting_netflow_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="NetFlow",
     *     path="System -> Settings -> NetFlow",
     *     formTypes={
     *         SettingNetFlowOptionsType::class
     *     },
     *     extra={
     *         "Excluded IP addresses"
     *     }
     * )
     */
    public function editAction(Request $request): Response
    {
        $formFactory = $this->container->get('form.factory');

        $optionsManager = $this->get(OptionsManager::class);
        $options = $optionsManager->loadOptionsIntoDataClass(NetFlowOptionsData::class);

        $optionsForm = $formFactory
            ->createNamedBuilder(
                'optionsForm',
                SettingNetFlowOptionsType::class,
                $options
            )
            ->getForm();

        $optionsForm->handleRequest($request);
        if ($optionsForm->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($optionsForm->isSubmitted() && $optionsForm->isValid()) {
            $optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_netflow_edit');
        }

        $gridExcludedIp = $this->get(NetFlowExcludedIpGridFactory::class)->create();
        if ($response = $gridExcludedIp->processMultiAction()) {
            return $response;
        }
        if ($parameters = $gridExcludedIp->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'setting/netflow/edit.html.twig',
            [
                'optionsForm' => $optionsForm->createView(),
                'serverIpSet' => (bool) $this->getOption(Option::SERVER_IP),
                'gridExcludedIp' => $gridExcludedIp,
            ]
        );
    }

    /**
     * @Route("/excluded-ip/{id}/delete", name="setting_netflow_excluded_ip_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteExcludedIpAction(NetflowExcludedIp $netflowExcludedIp)
    {
        $this->get(NetflowExcludedIpFacade::class)->handleDelete($netflowExcludedIp);

        $this->addTranslatedFlash('success', 'Item has been removed.');

        return $this->redirectToRoute('setting_netflow_edit');
    }

    /**
     * @Route("/excluded-ip/{id}/edit", name="setting_netflow_excluded_ip_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @CsrfToken()
     * @Permission("edit")
     */
    public function editExcludedIpAction(Request $request, NetflowExcludedIp $netflowExcludedIp)
    {
        $form = $this->createForm(
            NetflowExcludedIpType::class,
            $netflowExcludedIp,
            [
                'action' => $this->generateUrl(
                    'setting_netflow_excluded_ip_edit',
                    ['id' => $netflowExcludedIp->getId()]
                ),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(NetflowExcludedIpFacade::class)->handleEdit($netflowExcludedIp);

            $this->addTranslatedFlash('success', 'Item has been saved.');

            return $this->createAjaxRedirectResponse('setting_netflow_edit');
        }

        return $this->render(
            'setting/netflow/components/excluded_ip_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/excluded-ip/new", name="setting_netflow_excluded_ip_new")
     * @Method({"GET","POST"})
     * @CsrfToken()
     * @Permission("edit")
     */
    public function newExcludedIpAction(Request $request): Response
    {
        $data = new NetflowExcludedIp();
        $form = $this->createForm(
            NetflowExcludedIpType::class,
            $data,
            [
                'action' => $this->generateUrl('setting_netflow_excluded_ip_new'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(NetflowExcludedIpFacade::class)->handleNew($data);

            $this->addTranslatedFlash('success', 'Item has been saved.');

            return $this->createAjaxRedirectResponse('setting_netflow_edit');
        }

        return $this->render(
            'setting/netflow/components/excluded_ip_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
