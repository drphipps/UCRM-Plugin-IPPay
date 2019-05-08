<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\NotificationTemplate;
use AppBundle\Util\Arrays;
use Doctrine\ORM\EntityManagerInterface;

class NotificationTemplateDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getTemplate(int $templateId): NotificationTemplate
    {
        return $this->entityManager->getRepository(NotificationTemplate::class)->find($templateId);
    }

    public function getAllEmailTemplates(): array
    {
        $templates = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(NotificationTemplate::class, 't')
            ->where('t.type IN (:types)')
            ->setParameter('types', NotificationTemplate::EMAIL_TYPES)
            ->indexBy('t', 't.id')
            ->getQuery()
            ->getResult();

        $categories = NotificationTemplate::EMAIL_TYPES_CATEGORIES;
        foreach ($categories as $category => $items) {
            foreach ($items as $key => $itemId) {
                $categories[$category][$key] = $templates[$itemId] ?? null;
            }
            $categories[$category] = array_filter($categories[$category]);
        }

        return $categories;
    }

    public function getAllSuspensionTemplates(): array
    {
        $templates = $this->entityManager->getRepository(NotificationTemplate::class)->findBy(
            [
                'type' => NotificationTemplate::SUSPENSION_TYPES,
            ]
        );

        Arrays::sortByArray($templates, NotificationTemplate::SUSPENSION_TYPES_SORT, 'type');

        return $templates;
    }
}
