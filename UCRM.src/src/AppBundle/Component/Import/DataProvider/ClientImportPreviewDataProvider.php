<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\DataProvider;

use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ServiceImportItem;
use AppBundle\Repository\ClientImportItemRepository;
use AppBundle\Repository\ServiceImportItemRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class ClientImportPreviewDataProvider
{
    private const ITEM_COUNT_LIMIT = 100;

    /**
     * @var ClientImportItemRepository|ObjectRepository
     */
    private $clientImportItemRepository;

    /**
     * @var ServiceImportItemRepository|ObjectRepository
     */
    private $serviceImportItemRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->clientImportItemRepository = $entityManager->getRepository(ClientImportItem::class);
        $this->serviceImportItemRepository = $entityManager->getRepository(ServiceImportItem::class);
    }

    public function get(ClientImport $import): array
    {
        $itemCount = $this->clientImportItemRepository->getCountWithoutServiceItems($import)
            + $this->serviceImportItemRepository->getCount($import);

        // It is easily possible to load more items than needed if any client item has more than one service,
        // but no solution comes to mind right now for it.
        $items = $this->clientImportItemRepository->getItems(
            $import,
            self::ITEM_COUNT_LIMIT
        );

        return [
            'items' => $items,
            'itemCount' => $itemCount,
            'itemCountLimit' => self::ITEM_COUNT_LIMIT,
            'itemCountOverLimit' => $itemCount > self::ITEM_COUNT_LIMIT,
            'errorSummary' => $import->getErrorSummary(),
            'hasErrors' => $import->getErrorSummary()->getErroneousClientCount() > 0,
        ];
    }
}
