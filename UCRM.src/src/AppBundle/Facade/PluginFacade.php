<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\DataProvider\AppKeyDataProvider;
use AppBundle\Entity\AppKey;
use AppBundle\Entity\Plugin;
use AppBundle\Event\Plugin\PluginAddEvent;
use AppBundle\Event\Plugin\PluginDeleteEvent;
use AppBundle\Event\Plugin\PluginEditEvent;
use AppBundle\Exception\PluginException;
use AppBundle\Exception\PluginManifestException;
use AppBundle\Exception\PluginNotConfiguredException;
use AppBundle\Exception\PluginUpdateConfirmationException;
use AppBundle\Exception\PluginUploadException;
use AppBundle\Factory\Plugin\PluginConfigurationDataFactory;
use AppBundle\FileManager\PluginDataFileManager;
use AppBundle\FileManager\PluginFileManager;
use AppBundle\Form\PluginConfigurationItemsType;
use AppBundle\Service\Plugin\PluginManifest;
use AppBundle\Service\Plugin\PluginManifestConfiguration;
use AppBundle\Util\Helpers;
use AppBundle\Util\Strings;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use TransactionEventsBundle\TransactionDispatcher;

class PluginFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PluginFileManager
     */
    private $pluginFileManager;

    /**
     * @var PluginDataFileManager
     */
    private $pluginDataFileManager;

    /**
     * @var AppKeyDataProvider
     */
    private $appKeyDataProvider;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var PluginConfigurationDataFactory
     */
    private $pluginConfigurationDataFactory;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        EntityManagerInterface $entityManager,
        PluginFileManager $pluginFileManager,
        PluginDataFileManager $pluginDataFileManager,
        AppKeyDataProvider $appKeyDataProvider,
        TransactionDispatcher $transactionDispatcher,
        PluginConfigurationDataFactory $pluginConfigurationDataFactory,
        FormFactoryInterface $formFactory
    ) {
        $this->entityManager = $entityManager;
        $this->pluginFileManager = $pluginFileManager;
        $this->pluginDataFileManager = $pluginDataFileManager;
        $this->appKeyDataProvider = $appKeyDataProvider;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->pluginConfigurationDataFactory = $pluginConfigurationDataFactory;
        $this->formFactory = $formFactory;
        $this->filesystem = new Filesystem();
    }

    public function handleInstall(string $zipUrl): Plugin
    {
        $file = $this->downloadFile($zipUrl);
        $zip = $this->pluginFileManager->getVerifiedZipArchive($file);
        $manifest = $this->loadManifestFromZip($zip);
        $plugin = $this->findPluginByName($manifest);

        // If the plugin already exists, force update confirmation first.
        if ($plugin) {
            $tmpZipArchiveFileName = basename($file);
            $oldManifest = $this->loadPluginManifest($plugin);
            throw new PluginUpdateConfirmationException($tmpZipArchiveFileName, $oldManifest, $manifest);
        }

        return $this->saveNewPlugin($manifest, $zip, $file);
    }

    public function handleUpload(UploadedFile $uploadedFile): Plugin
    {
        $zip = $this->pluginFileManager->getVerifiedZipArchive($uploadedFile->getRealPath());
        $manifest = $this->loadManifestFromZip($zip);
        $plugin = $this->findPluginByName($manifest);

        // If the plugin already exists, force update confirmation first.
        if ($plugin) {
            $tmpZipArchiveFileName = basename(Helpers::getTemporaryFile());
            $uploadedFile->move(sys_get_temp_dir(), $tmpZipArchiveFileName);
            $oldManifest = $this->loadPluginManifest($plugin);

            throw new PluginUpdateConfirmationException($tmpZipArchiveFileName, $oldManifest, $manifest);
        }

        return $this->saveNewPlugin($manifest, $zip, $uploadedFile->getRealPath());
    }

    private function saveNewPlugin(PluginManifest $manifest, \ZipArchive $zip, string $file): Plugin
    {
        $this->pluginFileManager->save($zip, $manifest);
        $plugin = new Plugin();

        try {
            $this->transactionDispatcher->transactional(
                function (EntityManagerInterface $entityManager) use ($plugin, $manifest) {
                    $this->setPluginInformationFromManifest($plugin, $manifest);
                    $entityManager->persist($plugin);

                    yield new PluginAddEvent($plugin);
                }
            );

            $this->entityManager->refresh($plugin);
        } catch (\Exception $exception) {
            $this->pluginFileManager->delete($plugin);

            throw $exception;
        } finally {
            $this->filesystem->remove($file);
        }

        return $plugin;
    }

    private function updatePlugin(Plugin $plugin, PluginManifest $manifest, \ZipArchive $zip, string $file): Plugin
    {
        if ($this->pluginDataFileManager->isRunning($plugin)) {
            throw new PluginUploadException('Plugin you are trying to update is currently running.');
        }

        $this->pluginFileManager->upgrade($plugin, $zip, $manifest);

        try {
            $this->transactionDispatcher->transactional(
                function () use ($plugin, $manifest) {
                    $this->setPluginInformationFromManifest($plugin, $manifest);

                    try {
                        $this->checkPluginIsConfiguredAndCompatible($plugin);
                        // intentionally no setting enabled here, if the plugin is configured,
                        // leave it in the previous state
                        if ($plugin->isEnabled()) {
                            $this->pluginDataFileManager->createPublicSymlink($plugin);
                        }
                    } catch (PluginException $exception) {
                        // plugin is missing configuration after update, disable
                        $plugin->setEnabled(false);
                    }

                    yield new PluginEditEvent($plugin);
                }
            );

            $this->entityManager->refresh($plugin);
        } finally {
            $this->filesystem->remove($file);
        }

        return $plugin;
    }

    public function handleUpdate(string $tmpZipArchiveFileName): Plugin
    {
        $tmpZipArchivePath = sprintf(
            '%s/%s',
            rtrim(sys_get_temp_dir(), '/'),
            Strings::sanitizeFileName($tmpZipArchiveFileName)
        );
        if (! $this->filesystem->exists($tmpZipArchivePath)) {
            throw new PluginUploadException('Plugin archive does not exist on server, please try again.');
        }
        $zip = $this->pluginFileManager->getVerifiedZipArchive($tmpZipArchivePath);
        $manifest = $this->loadManifestFromZip($zip);
        $plugin = $this->findPluginByName($manifest);

        if (! $plugin) {
            throw new PluginUploadException('Plugin you are trying to update does not exist.');
        }

        return $this->updatePlugin($plugin, $manifest, $zip, $tmpZipArchivePath);
    }

    /**
     * @throws PluginException
     */
    public function handleEnable(Plugin $plugin): void
    {
        $this->checkPluginIsConfiguredAndCompatible($plugin);

        if (! $plugin->getAppKey()) {
            $appKey = new AppKey();
            $appKey->setCreatedDate(new \DateTime());
            $appKey->setType(AppKey::TYPE_WRITE);
            $appKey->setKey($this->appKeyDataProvider->getUniqueKey());
            $appKey->setName(sprintf('plugin_%s', $plugin->getName()));
            $appKey->setPlugin($plugin);
            $this->entityManager->persist($appKey);
            $plugin->setAppKey($appKey);
        }

        $plugin->setEnabled(true);
        $this->handleEdit($plugin);
        $this->pluginDataFileManager->createPublicSymlink($plugin);
    }

    public function handleDisable(Plugin $plugin): void
    {
        $plugin->setEnabled(false);
        $this->handleEdit($plugin);
        $this->pluginDataFileManager->deletePublicSymlink($plugin);
    }

    public function handleEdit(Plugin $plugin)
    {
        $this->transactionDispatcher->transactional(
            function () use ($plugin) {
                yield new PluginEditEvent($plugin);
            }
        );
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function handleExecute(Plugin $plugin): void
    {
        $this->pluginDataFileManager->requestExecution($plugin);
    }

    public function handleDelete(Plugin $plugin): void
    {
        if (
            $this->pluginDataFileManager->isRunning($plugin)
            && ! $this->pluginDataFileManager->isHung($plugin)
        ) {
            throw new PluginException('Plugin is currently running. You can disable it to prevent further execution.');
        }

        $this->transactionDispatcher->transactional(
            function () use ($plugin) {
                if ($plugin->getAppKey()) {
                    $plugin->getAppKey()->setDeletedAt(new \DateTime());
                    $plugin->getAppKey()->setPlugin(null);
                }

                $id = $plugin->getId();
                $this->entityManager->remove($plugin);

                yield new PluginDeleteEvent($plugin, $id);
            }
        );

        $this->pluginDataFileManager->deletePublicSymlink($plugin);
        $this->pluginFileManager->delete($plugin);
    }

    public function regenerateSymlinks(): void
    {
        $plugins = $this->entityManager->getRepository(Plugin::class)->findBy(
            [
                'enabled' => true,
            ]
        );

        foreach ($plugins as $plugin) {
            try {
                $this->checkPluginIsConfiguredAndCompatible($plugin);
            } catch (PluginException $exception) {
                continue;
            }

            $this->pluginDataFileManager->createPublicSymlink($plugin);
        }
    }

    /**
     * @throws PluginException
     */
    private function checkPluginIsConfiguredAndCompatible(Plugin $plugin): void
    {
        $config = $this->pluginDataFileManager->getConfig($plugin);
        try {
            $manifest = $this->pluginDataFileManager->getManifest($plugin);
        } catch (FileNotFoundException $exception) {
            throw new PluginNotConfiguredException(
                'Plugin must be configured first.',
                $exception->getCode(),
                $exception
            );
        }

        if (! $manifest->isUcrmVersionCompliant) {
            throw new PluginException('Plugin is not compatible with your UCRM version.');
        }

        foreach ($manifest->configuration as $item) {
            if (
                $item->required
                && (! $config || ! array_key_exists($item->key, $config) || $config[$item->key] === null)
            ) {
                throw new PluginNotConfiguredException('Plugin must be configured first.');
            }
        }

        $config = $this->generateNullConfig($manifest, $config);
        $this->pluginDataFileManager->saveConfig($plugin, $config);

        $this->checkPluginConfigurationIsCompatible($plugin, $manifest, $config);
    }

    /**
     * Detects problem with previously installed plugin having incompatible configuration types.
     * E.g. previous configuration with key "useToken" was TextType and now it's CheckboxType.
     *
     * @throws PluginNotConfiguredException
     */
    private function checkPluginConfigurationIsCompatible(Plugin $plugin, PluginManifest $manifest, array $config): void
    {
        $data = $this->pluginConfigurationDataFactory->create($plugin, $manifest, $config);

        $form = $this->formFactory->create(
            PluginConfigurationItemsType::class,
            $data->configuration,
            [
                'configuration_items' => $manifest->configuration,
            ]
        );

        if ($form->getErrors(true)->count() > 0) {
            throw new PluginNotConfiguredException('Plugin must be configured first.');
        }
    }

    private function setPluginInformationFromManifest(Plugin $plugin, PluginManifest $manifest): void
    {
        $plugin->setName($manifest->information->name);
        $plugin->setDisplayName($manifest->information->displayName);
        $plugin->setDescription($manifest->information->description);
        $plugin->setUrl($manifest->information->url);
        $plugin->setVersion($manifest->information->version);
        $plugin->setMinUcrmVersion($manifest->information->ucrmVersionCompliancyMin);
        $plugin->setMaxUcrmVersion($manifest->information->ucrmVersionCompliancyMax);
        $plugin->setAuthor($manifest->information->author);
    }

    private function downloadFile(string $zipUrl): string
    {
        $file = Helpers::getTemporaryFile();
        $client = new Client();
        try {
            $response = $client->get($zipUrl, ['save_to' => $file]);
        } catch (RequestException $exception) {
            throw new PluginUploadException('Plugin could not be downloaded.');
        }

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new PluginUploadException('Plugin could not be downloaded.');
        }

        return $file;
    }

    /**
     * @throws PluginUploadException
     */
    private function loadManifestFromZip(\ZipArchive $zip): PluginManifest
    {
        try {
            $manifest = $this->pluginFileManager->getManifestFromZip($zip);
        } catch (PluginManifestException $exception) {
            throw new PluginUploadException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $manifest;
    }

    private function findPluginByName(PluginManifest $manifest): ?Plugin
    {
        return $this->entityManager->getRepository(Plugin::class)->findOneBy(
            [
                'name' => $manifest->information->name,
            ]
        );
    }

    private function loadPluginManifest(Plugin $plugin): ?PluginManifest
    {
        try {
            return $this->pluginDataFileManager->getManifest($plugin);
        } catch (FileNotFoundException | PluginManifestException $exception) {
            return null;
        }
    }

    /**
     * Generates/appends config with previously nonexistent configuration keys.
     */
    private function generateNullConfig(PluginManifest $pluginManifest, ?array $config): array
    {
        $data = [];
        foreach ($pluginManifest->configuration as $configuration) {
            if ($config && array_key_exists($configuration->key, $config)) {
                $data[$configuration->key] = $config[$configuration->key];
                continue;
            }

            $data[$configuration->key] = $configuration->type === PluginManifestConfiguration::TYPE_CHECKBOX ? false : null;
        }

        return $data;
    }
}
