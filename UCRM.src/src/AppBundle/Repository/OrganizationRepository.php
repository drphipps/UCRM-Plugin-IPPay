<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Country;
use AppBundle\Entity\Organization;

class OrganizationRepository extends BaseRepository
{
    public function getCountOfSelected(): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(1)')
            ->where('o.selected = TRUE');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int  $organizationId
     * @param bool $selected
     */
    public function setDefault($organizationId, $selected)
    {
        $qb = $this->createQueryBuilder('o')
            ->update()
            ->set('o.selected', $selected ? 'true' : 'false')
            ->where('o.id = :organization_id')
            ->setParameter('organization_id', $organizationId);

        return $qb->getQuery()->execute();
    }

    public function existsAny(): bool
    {
        return (bool) $this->createQueryBuilder('o')
            ->select('1')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getFirstSelected(): ?Organization
    {
        return $this->findOneBy([], ['selected' => 'DESC', 'id' => 'ASC']) ?: null;
    }

    /**
     * @return Organization[]
     */
    public function getAllFirstSelected(): array
    {
        return $this->findBy([], ['selected' => 'DESC', 'id' => 'ASC']);
    }

    public function existsOtherThanUSA(): bool
    {
        return (bool) $this->createQueryBuilder('o')
            ->select('1')
            ->where('o.country != :country')
            ->setParameter('country', Country::COUNTRY_UNITED_STATES)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Gets a default (selected) organization, or if there is only single organization, then that.
     */
    public function getSelectedOrAlone(): ?Organization
    {
        $organizations = $this->findBy([], ['selected' => 'DESC', 'id' => 'ASC'], 2);
        /** @var Organization|null $first */
        $first = reset($organizations);

        if ($first && ($first->getSelected() || count($organizations) === 1)) {
            return $first;
        }

        return null;
    }

    public function getCount(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array
     */
    public function findAllForm()
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.id, o.name')
            ->orderBy('o.name', 'ASC');
        $result = $qb->getQuery()->getArrayResult();
        $organizations = [];

        foreach ($result as $item) {
            $organizations[$item['id']] = $item['name'];
        }

        return $organizations;
    }
}
