<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\DataProvider\DocumentDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Document;
use AppBundle\Entity\User;
use AppBundle\Event\Document\DocumentDeleteEvent;
use AppBundle\Util\Strings;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings as NStrings;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TransactionEventsBundle\TransactionDispatcher;
use ZipStream\ZipStream;

class DocumentFacade
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var DocumentDataProvider
     */
    private $documentDataProvider;

    public function __construct(
        string $rootDir,
        EntityManager $entityManager,
        TransactionDispatcher $transactionDispatcher,
        DocumentDataProvider $documentDataProvider
    ) {
        $this->rootDir = $rootDir;
        $this->entityManager = $entityManager;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->documentDataProvider = $documentDataProvider;
    }

    public function handleNew(Client $client, User $user, File $file, ?string $originalFilename): void
    {
        $filename = $originalFilename ?: $file->getFilename();
        $path = NStrings::replace($file->getPathname(), sprintf('~^%s~', preg_quote($this->rootDir, '~')), '');

        $document = new Document();
        $document->setClient($client);
        $document->setUser($user);
        $document->setCreatedDate(new \DateTime());
        $document->setName(Strings::sanitizeFileName($filename));
        $document->setPath($path);
        $document->setSize($file->getSize());
        $document->setType($this->guessDocumentType($file));

        $this->entityManager->transactional(
            function () use ($document) {
                $this->entityManager->persist($document);
            }
        );
    }

    public function handleDelete(Document $document): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManager $entityManager) use ($document) {
                $id = $document->getId();

                $entityManager->remove($document);

                yield new DocumentDeleteEvent($document, $id);
            }
        );
    }

    /**
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultiple(array $ids): array
    {
        $count = 0;
        $deleted = 0;

        $this->transactionDispatcher->transactional(
            function (EntityManager $entityManager) use ($ids, &$deleted, &$count) {
                $documents = $entityManager->getRepository(Document::class)->findBy(
                    [
                        'id' => $ids,
                    ]
                );

                $count = count($documents);

                foreach ($documents as $document) {
                    $id = $document->getId();

                    $entityManager->remove($document);

                    yield new DocumentDeleteEvent($document, $id);

                    ++$deleted;
                }
            }
        );

        return [$deleted, $count - $deleted];
    }

    public function handleStreamedZipDownload(array $ids): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($ids) {
                $zip = new ZipStream(sprintf('files-%s.zip', date('YmdHis')));
                $paths = $this->documentDataProvider->getPathsForDocuments(
                    $this->entityManager->getRepository(Document::class)->findBy(
                        [
                            'id' => $ids,
                        ]
                    )
                );

                $names = [];
                foreach ($paths as $path) {
                    $name = Strings::sanitizeFileName($path->getName());
                    while (in_array($name, $names, true)) {
                        $name = sprintf('%s_%s', uniqid(), Strings::sanitizeFileName($path->getName()));
                    }
                    $names[] = $name;
                    $zip->addFileFromPath($name, $path->getPath());
                }

                $zip->finish();
            }
        );
    }

    private function guessDocumentType(File $file): string
    {
        $ext = NStrings::lower($file->getExtension());

        if (in_array($ext, Document::TYPE_DOCUMENT_EXT, true)) {
            return Document::TYPE_DOCUMENT;
        }
        if (in_array($ext, Document::TYPE_IMAGE_EXT, true)) {
            return Document::TYPE_IMAGE;
        }

        return Document::TYPE_OTHER;
    }
}
