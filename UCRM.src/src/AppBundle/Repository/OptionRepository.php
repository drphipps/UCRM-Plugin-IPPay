<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Option;
use AppBundle\Util\Helpers;

class OptionRepository extends BaseRepository
{
    public function getAllOptions(): array
    {
        $result = $this->createQueryBuilder('o')
            ->select('o.code', 'o.value')
            ->getQuery()
            ->getArrayResult();

        $options = [];

        foreach ($result as $row) {
            $options[$row['code']] = Helpers::typeCastNullable(
                Option::TYPE_CASTS[$row['code']] ?? 'string',
                $row['value']
            );
        }

        return $options;
    }
}
