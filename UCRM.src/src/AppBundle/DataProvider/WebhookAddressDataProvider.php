<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\WebhookAddress;
use Doctrine\ORM\EntityManagerInterface;

class WebhookAddressDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return WebhookAddress[]
     */
    public function getAllActive(): array
    {
        return $this->entityManager->getRepository(WebhookAddress::class)->findBy(
            [
                'isActive' => true,
                'deletedAt' => null,
            ]
        );
    }

    public function existsActive(): bool
    {
        return (bool) $this->entityManager->getRepository(WebhookAddress::class)
            ->createQueryBuilder('wa')
            ->select('1')
            ->where('wa.isActive = true')
            ->andWhere('wa.deletedAt IS NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string[] $urls
     */
    public function getByUrls(array $urls): ?WebhookAddress
    {
        $webhookUrls = array_filter($urls);
        if (! count($webhookUrls)) {
            return null;
        }

        return $this->entityManager->getRepository(WebhookAddress::class)->findOneBy(
            [
                'url' => $webhookUrls,
                'deletedAt' => null,
            ]
        );
    }

    public function getByPublicUrl(string $publicUrl): ?WebhookAddress
    {
        return $this->entityManager->getRepository(WebhookAddress::class)->findOneBy(
            [
                'url' => $publicUrl,
                'deletedAt' => null,
            ]
        );
    }
}
