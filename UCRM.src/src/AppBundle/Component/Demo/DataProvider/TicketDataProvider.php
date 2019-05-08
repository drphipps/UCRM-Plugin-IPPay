<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\DataProvider;

use TicketingBundle\Entity\Ticket;

class TicketDataProvider extends AbstractDataProvider
{
    public function get(): array
    {
        $tickets = [];
        $ticketParams = [];
        $activity = [];
        $comments = [];
        $commentParams = [];
        $adminIds = $this->demoDataRepository->getAdminIds();
        $adminIds[] = null;
        $clientIds = $this->demoDataRepository->getNonLeadClientIds();

        for ($i = 0; $i < 50; ++$i) {
            $date = $this->faker->dateTimeThisMonth()->format($this->databasePlatform->getDateTimeFormatString());

            $tickets[] = sprintf(
                '(%s, ?, %s, \'%s\', \'%s\', \'%s\', \'%s\')',
                $this->faker->randomElement($clientIds),
                $this->faker->randomElement($adminIds) ?? 'NULL',
                $this->faker->randomElement(Ticket::STATUSES_NUMERIC),
                $date,
                $date,
                $date
            );
            $ticketParams[] = $this->getTicketTitle();

            $activity[] = sprintf(
                '(%s, \'%s\', \'%s\')',
                sprintf('(SELECT MIN(id) + %d FROM ticket)', $i),
                $date,
                'ticketcomment'
            );

            $comments[] = sprintf(
                '(%s, ?)',
                sprintf('(SELECT MIN(id) + %d FROM ticket_activity)', $i)
            );
            /** @var string[] $paragraphs */
            $paragraphs = $this->faker->paragraphs($this->faker->numberBetween(1, 4));
            $commentParams[] = implode(PHP_EOL . PHP_EOL, $paragraphs);
        }

        return [
            'DELETE FROM ticket',
            [
                'query' => sprintf(
                    'INSERT INTO ticket (client_id, subject, assigned_user_id, status, created_at, last_comment_at, last_activity) VALUES %s',
                    implode(',', $tickets)
                ),
                'params' => $ticketParams,
            ],
            sprintf(
                'INSERT INTO ticket_activity (ticket_id, created_at, dtype) VALUES %s',
                implode(',', $activity)
            ),
            [
                'query' => sprintf(
                    'INSERT INTO ticket_comment (id, body) VALUES %s',
                    implode(',', $comments)
                ),
                'params' => $commentParams,
            ],
        ];
    }

    private function getTicketTitle(): string
    {
        return $this->faker->randomElement(
            [
                'No Connection',
                'Slow speed',
                'Outage detected',
                'Call John asap!!',
                'Slow speed again',
                'No invoice delivered',
                'Service period invoiced twice',
                'Wrong recurring payment subscription',
                'Postpone Nick\'s suspension',
            ]
        );
    }
}
