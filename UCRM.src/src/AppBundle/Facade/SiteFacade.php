<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\Site;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class SiteFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getAllSites(
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $repository = $this->em->getRepository(Site::class);

        return $repository->findBy(['deletedAt' => null], ['id' => 'ASC'], $limit, $offset);
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->em->getRepository(Site::class)
            ->createQueryBuilder('s')
            ->andWhere('s.deletedAt IS NULL');
    }

    public function handleDelete(Site $site): bool
    {
        if (! $this->setDeleted($site)) {
            return false;
        }

        $this->em->flush();

        return true;
    }

    /**
     * @return array [$deleted, $failed]
     *
     * @throws \Exception
     */
    public function handleDeleteMultiple(array $ids): array
    {
        $sites = $this->em->getRepository(Site::class)->findBy(
            [
                'id' => $ids,
                'deletedAt' => null,
            ]
        );

        $count = count($sites);
        $deleted = 0;

        foreach ($sites as $site) {
            if (! $this->setDeleted($site)) {
                continue;
            }

            ++$deleted;
        }

        if ($deleted > 0) {
            $this->em->flush();
        }

        return [$deleted, $count - $deleted];
    }

    private function setDeleted(Site $site): bool
    {
        if ($site->getNotDeletedDevices()->count() > 0) {
            return false;
        }

        $site->setDeletedAt(new \DateTime());

        return true;
    }
}
