<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\Export\ExportPathData;
use AppBundle\Entity\Client;
use AppBundle\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Filesystem\Filesystem;

class DocumentDataProvider
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        string $rootDir,
        EntityManagerInterface $entityManager
    ) {
        $this->rootDir = $rootDir;
        $this->entityManager = $entityManager;
    }

    /**
     * @return ExportPathData[]
     */
    public function getAllPathsForClient(Client $client): array
    {
        return $this->getPathsForDocuments(
            $this->entityManager->getRepository(Document::class)->findBy(
                [
                    'client' => $client,
                ]
            )
        );
    }

    /**
     * @param Document[] $documents
     *
     * @return ExportPathData[]
     */
    public function getPathsForDocuments(array $documents): array
    {
        $filesystem = new Filesystem();
        $paths = [];
        foreach ($documents as $document) {
            $documentPath = $this->rootDir . $document->getPath();
            if (! $filesystem->exists($documentPath)) {
                continue;
            }

            $paths[] = new ExportPathData($document->getName(), $documentPath);
        }

        return $paths;
    }

    public function getGridModel(Client $client): QueryBuilder
    {
        return $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->addSelect('u')
            ->leftJoin('d.user', 'u')
            ->where('d.client = :clientId')
            ->setParameter('clientId', $client->getId());
    }
}
