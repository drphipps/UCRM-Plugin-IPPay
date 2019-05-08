<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Loader;

use AppBundle\Component\Import\Builder\ClientImportItemValidationErrorsBuilderFactory;
use AppBundle\Component\Import\Builder\ServiceImportItemValidationErrorsBuilderFactory;
use AppBundle\Component\Import\DataProvider\TransformerEntityDataFactory;
use AppBundle\Component\Import\Transformer\ClientImportItemToClientTransformer;
use AppBundle\Component\Import\Transformer\ServiceImportItemToServiceTransformer;
use AppBundle\Entity\Client;
use AppBundle\Entity\Import\ClientImportItem;

class ClientImportItemToClientLoader
{
    /**
     * @var ClientImportItemToClientTransformer
     */
    private $clientImportItemToClientTransformer;

    /**
     * @var ServiceImportItemToServiceTransformer
     */
    private $serviceImportItemToServiceTransformer;

    /**
     * @var TransformerEntityDataFactory
     */
    private $transformerEntityDataFactory;

    /**
     * @var ClientImportItemValidationErrorsBuilderFactory
     */
    private $clientImportItemValidationErrorsBuilderFactory;

    /**
     * @var ServiceImportItemValidationErrorsBuilderFactory
     */
    private $serviceImportItemValidationErrorsBuilderFactory;

    public function __construct(
        ClientImportItemToClientTransformer $clientImportItemToClientTransformer,
        ServiceImportItemToServiceTransformer $serviceImportItemToServiceTransformer,
        TransformerEntityDataFactory $transformerEntityDataFactory,
        ClientImportItemValidationErrorsBuilderFactory $clientImportItemValidationErrorsBuilderFactory,
        ServiceImportItemValidationErrorsBuilderFactory $serviceImportItemValidationErrorsBuilderFactory
    ) {
        $this->clientImportItemToClientTransformer = $clientImportItemToClientTransformer;
        $this->serviceImportItemToServiceTransformer = $serviceImportItemToServiceTransformer;
        $this->transformerEntityDataFactory = $transformerEntityDataFactory;
        $this->clientImportItemValidationErrorsBuilderFactory = $clientImportItemValidationErrorsBuilderFactory;
        $this->serviceImportItemValidationErrorsBuilderFactory = $serviceImportItemValidationErrorsBuilderFactory;
    }

    public function load(ClientImportItem $clientImportItem): ?Client
    {
        $transformerEntityData = $this->transformerEntityDataFactory->create();

        if (! $clientImportItem->isDoImport() || $clientImportItem->getValidationErrors()) {
            return null;
        }

        $clientValidationErrorsBuilder = $this->clientImportItemValidationErrorsBuilderFactory->create();
        $client = $this->clientImportItemToClientTransformer->transform(
            $clientImportItem,
            $transformerEntityData,
            $clientValidationErrorsBuilder
        );

        if ($clientValidationErrorsBuilder->getValidationErrors()->hasErrors()) {
            unset($client);

            return null;
        }

        foreach ($clientImportItem->getServiceItems() as $serviceItem) {
            if (! $serviceItem->isDoImport() || $serviceItem->getValidationErrors()) {
                continue;
            }

            $serviceValidationErrorsBuilder = $this->serviceImportItemValidationErrorsBuilderFactory->create();
            $service = $this->serviceImportItemToServiceTransformer->transform(
                $serviceItem,
                $client,
                $transformerEntityData,
                $serviceValidationErrorsBuilder,
                null
            );

            if ($serviceValidationErrorsBuilder->getValidationErrors()->hasErrors()) {
                unset($service);

                continue;
            }

            $client->addService($service);
        }

        return $client;
    }
}
