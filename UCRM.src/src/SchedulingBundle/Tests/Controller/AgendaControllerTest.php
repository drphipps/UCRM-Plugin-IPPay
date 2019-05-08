<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Tests\Controller;

use Tests\Functional\AdminWebTestCase;

class AgendaControllerTest extends AdminWebTestCase
{
    /**
     * @group AgendaController
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function urlProvider(): array
    {
        return [
            ['/scheduling/agenda'],
            ['/scheduling/agenda/my'],
        ];
    }
}
