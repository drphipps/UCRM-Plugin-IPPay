<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Command\Version\Checker;
use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\UcrmVersionDataProvider;
use AppBundle\Exception\UpdateException;
use AppBundle\Facade\UpdatesFacade;
use AppBundle\FileManager\UpdatesFileManager;
use AppBundle\Form\Data\Settings\UpdateChannelData;
use AppBundle\Form\UpdateChannelType;
use AppBundle\Security\Permission;
use AppBundle\Service\OptionsManager;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Nette\Utils\Random;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/tools/updates")
 */
class UpdatesController extends BaseController
{
    /**
     * @var UpdatesFileManager
     */
    private $updatesFileManager;

    /**
     * @var UcrmVersionDataProvider
     */
    private $ucrmVersionDataProvider;

    /**
     * @var UpdatesFacade
     */
    private $updatesFacade;

    /**
     * @var Checker
     */
    private $checker;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    public function __construct(
        UpdatesFileManager $updatesFileManager,
        UcrmVersionDataProvider $ucrmVersionDataProvider,
        UpdatesFacade $updatesFacade,
        Checker $checker,
        OptionsManager $optionsManager
    ) {
        $this->updatesFileManager = $updatesFileManager;
        $this->ucrmVersionDataProvider = $ucrmVersionDataProvider;
        $this->updatesFacade = $updatesFacade;
        $this->checker = $checker;
        $this->optionsManager = $optionsManager;
    }

    /**
     * @Route("", name="updates_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Updates", path="System -> Tools -> Updates")
     */
    public function indexAction(Request $request): Response
    {
        /** @var UpdateChannelData $options */
        $options = $this->optionsManager->loadOptionsIntoDataClass(UpdateChannelData::class);

        $form = $this->createForm(UpdateChannelType::class, $options);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, UpdatesController::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Update channel changed.');

            if ($request->isXmlHttpRequest()) {
                $this->invalidateTemplate(
                    'updates-container',
                    'updates/components/updates.html.twig',
                    $this->getViewParameters($form)
                );

                return $this->createAjaxResponse();
            }

            return $this->redirectToRoute('updates_index');
        }

        return $this->render(
            'updates/index.html.twig',
            array_merge(
                $this->getViewParameters($form),
                [
                    'updateLog' => $this->updatesFileManager->getUpdateLog(),
                ]
            )
        );
    }

    /**
     * @Route("/check", name="updates_check")
     * @Method("GET")
     * @Permission("view")
     * @CsrfToken()
     */
    public function checkAction(): Response
    {
        $this->checker->check();

        return $this->redirectToRoute('updates_index');
    }

    /**
     * @Route("/request-update/{version}", name="updates_request_update")
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function requestUpdateAction(string $version): Response
    {
        $updateFileAccessKey = Random::generate(128);
        try {
            $this->updatesFacade->requestUpdate($version, $updateFileAccessKey);
        } catch (UpdateException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute(
            'updates_index',
            [
                'key' => $updateFileAccessKey,
            ]
        );
    }

    /**
     * @return mixed[]
     */
    private function getViewParameters(FormInterface $form): array
    {
        return [
            'requestedUpdate' => $this->updatesFileManager->getRequestedUpdate(),
            'updateLog' => $this->updatesFileManager->getUpdateLog(),
            'currentVersion' => $this->ucrmVersionDataProvider->getCurrentVersion(),
            'currentChannel' => $this->ucrmVersionDataProvider->getCurrentUpdateChannel(),
            'latestAvailableVersions' => $this->ucrmVersionDataProvider->getLatestAvailableVersions(),
            'form' => $form->createView(),
        ];
    }
}
