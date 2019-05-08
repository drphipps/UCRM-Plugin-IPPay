<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

class GeneralRepository extends BaseRepository
{
    public function getAllOptions(): array
    {
        $result = $this->createQueryBuilder('g')
            ->select('g.code', 'g.value')
            ->getQuery()
            ->getArrayResult();

        $options = [];

        foreach ($result as $row) {
            $options[$row['code']] = $row['value'];
        }

        return $options;
    }
}
