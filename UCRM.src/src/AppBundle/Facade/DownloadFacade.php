<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Download;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Filesystem\Filesystem;

class DownloadFacade
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(string $rootDir, EntityManager $em, Filesystem $filesystem)
    {
        $this->rootDir = $rootDir;
        $this->em = $em;
        $this->filesystem = $filesystem;
    }

    public function getGridModel(User $user): QueryBuilder
    {
        return $this->em->getRepository(Download::class)
            ->createQueryBuilder('d')
            ->andWhere('d.user = :user OR d.user IS NULL')
            ->setParameter('user', $user);
    }

    public function handleDelete(Download $download): void
    {
        $path = $download->getPath() ? $this->rootDir . $download->getPath() : null;
        if ($path && $this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }

        $this->em->remove($download);
        $this->em->flush();
    }

    /**
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultiple(array $ids): array
    {
        $downloads = $this->em->getRepository(Download::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        $count = count($downloads);
        $deleted = 0;

        foreach ($downloads as $download) {
            $path = $download->getPath() ? $this->rootDir . $download->getPath() : null;
            if ($path && $this->filesystem->exists($path)) {
                $this->filesystem->remove($path);
            }
            $this->em->remove($download);

            ++$deleted;
        }

        if ($deleted > 0) {
            $this->em->flush();
        }

        return [$deleted, $count - $deleted];
    }
}
