<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\DataProvider;

use AppBundle\Entity\AppKey;

class AppKeyDataProvider extends AbstractDataProvider
{
    public function get(): array
    {
        $date = (new \DateTimeImmutable())->format($this->databasePlatform->getDateTimeFormatString());

        $appKeys = [
            '(?, ?, ?, ?)',
            '(?, ?, ?, ?)',
        ];
        $appKeyParams = [
            'Test app key read',
            'BvBdsGHQKc1dOOWGMcy0f07+2czCOb90zv5zxHNDhf4P5NFElwKsZWWV3QceKq5J',
            AppKey::TYPE_READ,
            $date,
            'Test app key write',
            '5YbpCSto7ffl/P/veJ/GK3U7K7zH6ZoHil7j5dorerSN8o+rlJJq6X/uFGZQF2WL',
            AppKey::TYPE_WRITE,
            $date,
        ];

        return [
            'DELETE FROM app_key',
            [
                'query' => sprintf(
                    'INSERT INTO app_key (name, key, type, created_date) VALUES %s',
                    implode(',', $appKeys)
                ),
                'params' => $appKeyParams,
            ],
        ];
    }
}
