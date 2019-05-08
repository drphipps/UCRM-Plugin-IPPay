<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LastSeenTicketCommentDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getLastSeenTicketComments(User $user, bool $onlyPublic): array
    {
        $lastSeenTicketComments = $this->entityManager->getConnection()
            ->fetchAll(
                sprintf(
                    '
                        SELECT 
                          lc.ticket_id,
                          (SELECT
                            COUNT(itc.id) AS unread
                            FROM ticket_comment itc
                            INNER JOIN ticket_activity ita ON ita.id = itc.id
                            WHERE 
                              ita.ticket_id = lc.ticket_id
                              AND itc.id > lc.last_seen_ticket_comment_id
                              %s
                          ) AS unread
                        FROM last_seen_ticket_comment lc
                        INNER JOIN ticket_comment tc ON tc.id = lc.last_seen_ticket_comment_id
                        INNER JOIN ticket_activity ta ON ta.id = tc.id
                        WHERE
                          lc.user_id = :userId
                          %s
                    ',
                    $onlyPublic
                        ? 'AND ita.public = true'
                        : '',
                    $onlyPublic
                        ? 'AND ta.public = true'
                        : ''
                ),
                [
                    'userId' => $user->getId(),
                ]
            );

        $converted = [];
        foreach ($lastSeenTicketComments as $lastSeenTicketComment) {
            $converted[$lastSeenTicketComment['ticket_id']] = $lastSeenTicketComment['unread'];
        }

        return $converted;
    }
}
