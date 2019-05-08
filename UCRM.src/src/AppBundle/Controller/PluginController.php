<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\PluginDataProvider;
use AppBundle\DataProvider\WebhookAddressDataProvider;
use AppBundle\Entity\Plugin;
use AppBundle\Exception\PluginException;
use AppBundle\Exception\PluginIntegrityException;
use AppBundle\Exception\PluginManifestException;
use AppBundle\Exception\PluginNotConfiguredException;
use AppBundle\Exception\PluginUpdateConfirmationException;
use AppBundle\Exception\PluginUploadException;
use AppBundle\Facade\PluginFacade;
use AppBundle\Facade\WebhookFacade;
use AppBundle\Factory\Plugin\PluginConfigurationDataFactory;
use AppBundle\FileManager\PluginDataFileManager;
use AppBundle\FileManager\PluginFileManager;
use AppBundle\Form\Data\PluginConfigurationData;
use AppBundle\Form\Data\PluginUploadData;
use AppBundle\Form\PluginConfigurationType;
use AppBundle\Form\PluginUploadType;
use AppBundle\Security\Permission;
use AppBundle\Util\Helpers;
use Ds\Set;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Route("/system/plugins")
 */
class PluginController extends BaseController
{
    /**
     * @Route("/install", name="plugin_install")
     * @Method({"GET"})
     * @CsrfToken()
     * @Permission("edit")
     */
    public function installAction(Request $request): Response
    {
        if (Helpers::isDemo()) {
            $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

            return $this->createAjaxRedirectResponse('plugin_index');
        }

        $zipUrl = $request->get('zipUrl');
        if (! $zipUrl || ! is_string($zipUrl)) {
            throw $this->createNotFoundException();
        }

        try {
            $plugin = $this->get(PluginFacade::class)->handleInstall($zipUrl);

            $this->addTranslatedFlash('success', 'Plugin successfully installed.');
        } catch (PluginUploadException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->createAjaxRedirectResponse('plugin_index');
        } catch (PluginUpdateConfirmationException $exception) {
            return $this->render(
                'plugins/update_confirmation.html.twig',
                [
                    'pluginZipArchiveFileName' => $exception->getTmpZipArchiveFileName(),
                    'oldManifest' => $exception->getOldManifest(),
                    'newManifest' => $exception->getNewManifest(),
                ]
            );
        }

        // Also enable right away.
        try {
            $this->get(PluginFacade::class)->handleEnable($plugin);
        } catch (PluginNotConfiguredException $exception) {
            $this->addTranslatedFlash('info', $exception->getMessage());

            return $this->createAjaxRedirectResponse(
                'plugin_configuration',
                [
                    'id' => $plugin->getId(),
                ]
            );
        } catch (PluginException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        return $this->createAjaxRedirectResponse(
            'plugin_show',
            [
                'id' => $plugin->getId(),
            ]
        );
    }

    /**
     * @Route("/upload", name="plugin_upload")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function uploadAction(Request $request): Response
    {
        if (Helpers::isDemo()) {
            $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

            return $this->createAjaxResponse();
        }

        $pluginUpload = new PluginUploadData();
        $form = $this->createForm(
            PluginUploadType::class,
            $pluginUpload,
            [
                'action' => $this->generateUrl('plugin_upload'),
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $plugin = $this->get(PluginFacade::class)->handleUpload($pluginUpload->pluginFile);
                $this->addTranslatedFlash('success', 'Plugin successfully uploaded.');

                return $this->createAjaxRedirectResponse(
                    'plugin_show',
                    [
                        'id' => $plugin->getId(),
                    ]
                );
            } catch (PluginUploadException $exception) {
                $form->get('pluginFile')->addError(
                    new FormError($exception->getMessage())
                );
            } catch (PluginUpdateConfirmationException $exception) {
                return $this->render(
                    'plugins/update_confirmation.html.twig',
                    [
                        'pluginZipArchiveFileName' => $exception->getTmpZipArchiveFileName(),
                        'oldManifest' => $exception->getOldManifest(),
                        'newManifest' => $exception->getNewManifest(),
                    ]
                );
            }
        }

        return $this->render(
            'plugins/upload.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/confirm-update/{file}", name="plugin_confirm_update", requirements={"file": "[a-zA-Z0-9]+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function confirmUpdateAction(string $file): Response
    {
        try {
            $plugin = $this->get(PluginFacade::class)->handleUpdate($file);
        } catch (PluginUploadException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->createAjaxRedirectResponse('plugin_index');
        }

        return $this->createAjaxRedirectResponse(
            'plugin_show',
            [
                'id' => $plugin->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="plugin_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(Plugin $plugin): Response
    {
        $pluginDataFileManager = $this->get(PluginDataFileManager::class);
        try {
            $pluginDataFileManager->verifyIntegrity($plugin);
            $manifest = $pluginDataFileManager->getManifest($plugin);
        } catch (PluginIntegrityException | PluginManifestException $exception) {
            return $this->render(
                'plugins/integrity.html.twig',
                [
                    'plugin' => $plugin,
                    'errorMessage' => $exception->getMessage(),
                ]
            );
        }

        $pluginDataProvider = $this->get(PluginDataProvider::class);
        $publicUrl = $pluginDataProvider->getPublicUrl($plugin);
        $localUrl = $pluginDataProvider->getLocalUrl($plugin);

        return $this->render(
            'plugins/show.html.twig',
            [
                'plugin' => $plugin,
                'pluginManifest' => $manifest,
                'pluginLog' => $pluginDataFileManager->getLog($plugin),
                'hasPublicSupport' => $pluginDataFileManager->hasPublicSupport($plugin),
                'isRunning' => $pluginDataFileManager->isRunning($plugin),
                'executionRequested' => $pluginDataFileManager->isExecutionRequested($plugin),
                'pluginWebhook' => $this->get(WebhookAddressDataProvider::class)->getByUrls([$localUrl, $publicUrl]),
                'pluginPublicUrl' => $publicUrl,
                'pluginLocalUrl' => $localUrl,
            ]
        );
    }

    /**
     * @Route("/{id}/webhook-test", name="plugin_webhook_test", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function webhookTestAction(Plugin $plugin): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, WebhookEndpointController::class);

        $pluginDataProvider = $this->get(PluginDataProvider::class);
        $publicUrl = $pluginDataProvider->getPublicUrl($plugin);
        $localUrl = $pluginDataProvider->getLocalUrl($plugin);
        $webhookAddress = $this->get(WebhookAddressDataProvider::class)->getByUrls([$localUrl, $publicUrl]);

        if ($webhookAddress) {
            $this->get(WebhookFacade::class)->handleTestSend($webhookAddress);
            $this->addTranslatedFlash('success', 'Webhook request has been sent.');
        } else {
            $this->addTranslatedFlash('error', 'No webhook found for this plugin.');
        }

        return $this->redirectToRoute('plugin_show', ['id' => $plugin->getId()]);
    }

    /**
     * @Route("/{id}/enable", name="plugin_enable", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function enableAction(Plugin $plugin): Response
    {
        if ($response = $this->verifyPluginIntegrityAndCompatibility($plugin)) {
            return $response;
        }

        try {
            $this->get(PluginFacade::class)->handleEnable($plugin);

            $this->addTranslatedFlash('success', 'Plugin has been enabled.');
        } catch (PluginNotConfiguredException $exception) {
            $this->addTranslatedFlash('info', $exception->getMessage());

            return $this->redirectToRoute(
                'plugin_configuration',
                [
                    'id' => $plugin->getId(),
                ]
            );
        } catch (PluginException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute(
            'plugin_show',
            [
                'id' => $plugin->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/disable", name="plugin_disable", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function disableAction(Plugin $plugin): Response
    {
        $this->get(PluginFacade::class)->handleDisable($plugin);
        $this->addTranslatedFlash('success', 'Plugin has been disabled.');

        return $this->redirectToRoute(
            'plugin_show',
            [
                'id' => $plugin->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/execute", name="plugin_execute", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function executeAction(Plugin $plugin): Response
    {
        if ($response = $this->verifyPluginIntegrityAndCompatibility($plugin)) {
            return $response;
        }

        if ($plugin->isEnabled()) {
            try {
                $this->get(PluginFacade::class)->handleExecute($plugin);
                $this->addTranslatedFlash('success', 'Plugin will be executed in a moment.');
            } catch (IOException $e) {
                $this->addTranslatedFlash(
                    'danger',
                    'Plugin execution request failed. Plugin directory is probably not writable.'
                );
            }
        } else {
            $this->addTranslatedFlash('danger', 'Plugin must be enabled first.');
        }

        return $this->redirectToRoute(
            'plugin_show',
            [
                'id' => $plugin->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/configuration", name="plugin_configuration", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function configurationAction(Request $request, Plugin $plugin): Response
    {
        if ($response = $this->verifyPluginIntegrityAndCompatibility($plugin)) {
            return $response;
        }

        $pluginDataFileManager = $this->get(PluginDataFileManager::class);
        $pluginFileManager = $this->get(PluginFileManager::class);
        $manifest = $pluginDataFileManager->getManifest($plugin);
        $config = $pluginDataFileManager->getConfig($plugin) ?? [];

        $existingFiles = new Set();
        $data = $this->get(PluginConfigurationDataFactory::class)->create($plugin, $manifest, $config, $existingFiles);

        $form = $this->createForm(
            PluginConfigurationType::class,
            $data,
            [
                'configuration_items' => $manifest->configuration,
                'show_enable_button' => ! $plugin->isEnabled(),
                'existingFiles' => $existingFiles->toArray(),
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plugin->setExecutionPeriod($data->executionPeriod);

            foreach ($manifest->configuration as $configuration) {
                if (
                    $configuration->type !== FileType::class
                    || (
                        array_key_exists($configuration->key, $data->configuration)
                        && $data->configuration[$configuration->key] instanceof UploadedFile
                    )
                ) {
                    continue;
                }
                $data->configuration[$configuration->key] = $config[$configuration->key] ?? null;
            }
            $this->get(PluginFacade::class)->handleEdit($plugin);

            try {
                foreach ($manifest->configuration as $configuration) {
                    $dataValue = $data->configuration[$configuration->key];

                    if ($configuration->type === DateTimeType::class) {
                        $data->configuration[$configuration->key] = $dataValue
                            ? $dataValue->format(\DateTime::ATOM)
                            : null;
                        continue;
                    }

                    if ($configuration->type === DateType::class) {
                        $data->configuration[$configuration->key] = $dataValue
                            ? $dataValue->format('Y-m-d')
                            : null;
                        continue;
                    }

                    if (
                        $configuration->type !== FileType::class
                        || ! array_key_exists($configuration->key, $data->configuration)
                        || ! $dataValue instanceof UploadedFile
                    ) {
                        continue;
                    }

                    /** @var UploadedFile $uploadedFile */
                    $uploadedFile = $dataValue;

                    $filename = $uploadedFile->getClientOriginalExtension()
                        ? sprintf(
                            '%s.%s',
                            $configuration->key,
                            $uploadedFile->getClientOriginalExtension()
                        )
                        : $configuration->key;

                    $pluginFileManager->createUploadedFile(
                        $plugin,
                        $uploadedFile,
                        $filename
                    );

                    $data->configuration[$configuration->key] = $filename;
                }

                $pluginDataFileManager->saveConfig($plugin, $data->configuration);
            } catch (FileNotFoundException $exception) {
                $this->addTranslatedFlash('error', 'Configuration could not be saved.');

                return $this->redirectToRoute(
                    'plugin_show',
                    [
                        'id' => $plugin->getId(),
                    ]
                );
            }

            if ($form->has('saveAndEnable')) {
                /** @var SubmitButton $saveAndEnableButton */
                $saveAndEnableButton = $form->get('saveAndEnable');
                if ($saveAndEnableButton->isClicked()) {
                    try {
                        $this->get(PluginFacade::class)->handleEnable($plugin);
                    } catch (PluginNotConfiguredException $exception) {
                        $this->addTranslatedFlash('info', $exception->getMessage());
                    } catch (PluginException $exception) {
                        $this->addTranslatedFlash('error', $exception->getMessage());
                    }
                }
            }

            $this->addTranslatedFlash('success', 'Configuration successfully saved.');

            return $this->redirectToRoute(
                'plugin_show',
                [
                    'id' => $plugin->getId(),
                ]
            );
        }

        return $this->render(
            'plugins/configuration.html.twig',
            [
                'plugin' => $plugin,
                'configuration' => $manifest->configuration,
                'form' => $form->createView(),
                'existingFiles' => $existingFiles,
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="plugin_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Plugin $plugin): Response
    {
        try {
            $this->get(PluginFacade::class)->handleDelete($plugin);
            $this->addTranslatedFlash('success', 'Plugin has been deleted.');

            return $this->redirectToRoute('plugin_index');
        } catch (PluginException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute(
                'plugin_show',
                [
                    'id' => $plugin->getId(),
                ]
            );
        }
    }

    /**
     * @Route("/{id}/file-delete/{key}", name="plugin_file_delete", requirements={"id": "\d+", "key": "\w+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteFileAction(Plugin $plugin, string $key): JsonResponse
    {
        $pluginFileManager = $this->get(PluginFileManager::class);

        $pluginDataFileManager = $this->get(PluginDataFileManager::class);
        $config = $pluginDataFileManager->getConfig($plugin) ?? [];

        if (array_key_exists($key, $config) && $config[$key]) {
            $pluginFileManager->removeUploadedFile($plugin, $config[$key]);
        }
        $config[$key] = null;
        $pluginDataFileManager->saveConfig($plugin, $config);

        $manifest = $pluginDataFileManager->getManifest($plugin);
        $data = new PluginConfigurationData();
        $data->executionPeriod = $plugin->getExecutionPeriod();
        $data->configuration = $config;

        $existingFiles = [];
        foreach ($manifest->configuration as $configuration) {
            if ($configuration->type === FileType::class) {
                $data->configuration[$configuration->key] = null;
                if ($config[$configuration->key]) {
                    $existingFiles[] = $configuration->key;
                }
            }
            if (in_array($configuration->type, [DateTimeType::class, DateType::class], true)) {
                $data->configuration[$configuration->key] = new \DateTime($config[$configuration->key] ?? 'now');
            }
        }

        $form = $this->createForm(
            PluginConfigurationType::class,
            $data,
            [
                'configuration_items' => $manifest->configuration,
                'show_enable_button' => ! $plugin->isEnabled(),
                'existingFiles' => $existingFiles,
            ]
        );

        $this->invalidateTemplate(
            'configuration_form',
            'plugins/components/configuration_form.html.twig',
            [
                'plugin' => $plugin,
                'configuration' => $manifest->configuration,
                'form' => $form->createView(),
                'existingFiles' => $existingFiles,
            ],
            true
        );

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/{id}/file-get/{key}", name="plugin_file_get", requirements={"id": "\d+", "key": "\w+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function getFileAction(Plugin $plugin, string $key): BinaryFileResponse
    {
        $fileDirectory = $this->get(PluginFileManager::class)->getDataFilesDirectory($plugin);
        $pluginDataFileManager = $this->get(PluginDataFileManager::class);
        $config = $pluginDataFileManager->getConfig($plugin) ?? [];

        try {
            $response = new BinaryFileResponse(sprintf('%s/%s', $fileDirectory, basename($config[$key])));
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $config[$key]);

        return $response;
    }

    private function verifyPluginIntegrityAndCompatibility(Plugin $plugin): ?Response
    {
        $pluginDataFileManager = $this->get(PluginDataFileManager::class);
        try {
            $pluginDataFileManager->verifyIntegrity($plugin);
            $manifest = $pluginDataFileManager->getManifest($plugin);
            if (! $manifest->isUcrmVersionCompliant) {
                throw new PluginException('Plugin is not compatible with your UCRM version.');
            }
        } catch (PluginException $exception) {
            if (! $exception instanceof PluginIntegrityException) {
                $this->addTranslatedFlash('error', $exception->getMessage());
            }

            return $this->redirectToRoute(
                'plugin_show',
                [
                    'id' => $plugin->getId(),
                ]
            );
        }

        return null;
    }
}
