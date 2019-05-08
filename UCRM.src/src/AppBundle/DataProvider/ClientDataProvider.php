<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Request\ClientCollectionRequest;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Refund;
use AppBundle\Entity\Service;
use AppBundle\Entity\User;
use AppBundle\Service\Options;
use AppBundle\Util\Invoicing;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ClientDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        Options $options,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->options = $options;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function getGridModel(bool $onlyArchived, bool $onlyLead): QueryBuilder
    {
        $qb = $this->entityManager->getRepository(Client::class)->getAllQueryBuilder();

        if ($onlyArchived) {
            $qb->andWhere('c.deletedAt IS NOT NULL');
        } else {
            $qb->andWhere('c.deletedAt IS NULL');
            // if archived and lead, we want to see it in "archived"
            if ($onlyLead) {
                $qb->andWhere('c.isLead = true');
            } else {
                $qb->andWhere('c.isLead = false');
            }
        }

        return $qb;
    }

    public function getGridPostFetchCallback(): \Closure
    {
        return function ($result) {
            $ids = array_map(
                function (array $row) {
                    return $row[0]->getId();
                },
                $result
            );

            $this->entityManager->getRepository(Client::class)->loadRelatedEntities('clientTags', $ids);
        };
    }

    public function getClientsActiveWithUser(string $order = 'client.id', string $direction = 'ASC'): array
    {
        $qb = $this->entityManager->getRepository(Client::class)->createQueryBuilder('client');

        return $qb->join('client.user', 'user')
            ->where('client.deletedAt IS NULL')
            ->andWhere('client.isLead = false')
            ->orderBy($order, $direction)
            ->getQuery()
            ->getResult();
    }

    public function getClientCollection(ClientCollectionRequest $request): array
    {
        $qb = $this->entityManager->getRepository(Client::class)->createQueryBuilder('client');

        $qb->join('client.user', 'user');
        $qb->andWhere('client.deletedAt IS NULL');

        if ($request->organizationId !== null) {
            $qb->andWhere('client.organization = :organization')
                ->setParameter('organization', $request->organizationId);
        }

        if ($request->isLead !== null) {
            $qb->andWhere('client.isLead = :isLead')
                ->setParameter('isLead', $request->isLead);
        }

        if ($request->userIdent) {
            $qb->andWhere('client.userIdent = :userIdent');
            $qb->setParameter('userIdent', $request->userIdent);
        }

        if ($request->getCustomAttributeKey()) {
            $qb->join('client.attributes', 'ca');
            $qb->join('ca.attribute', 'a');
            $qb->andWhere('a.key = :key');
            $qb->setParameter('key', $request->getCustomAttributeKey());
            $qb->andWhere('ca.value = :value');
            $qb->setParameter('value', $request->getCustomAttributeValue());
        }

        $order = $request->order ?: 'client.id';
        $direction = $request->direction ?: 'ASC';
        $qb->orderBy($order, $direction);

        if (in_array($order, ['user.firstName', 'user.lastName'], true)) {
            $qb->addOrderBy('client.companyName', $direction);
        }

        if ($request->limit !== null) {
            $qb->setMaxResults($request->limit);
        }

        if ($request->offset !== null) {
            $qb->setFirstResult($request->offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function getAllClientsForm(): array
    {
        /** @var Client[] $result */
        $result = $this->entityManager->getRepository(Client::class)->createQueryBuilder('c')
            ->select('c')
            ->join('c.user', 'u')
            ->where('c.deletedAt IS NULL')
            ->getQuery()
            ->getResult();

        $activeLabel = $this->translator->trans('Active clients');
        $leadsLabel = $this->translator->trans('Client leads');

        $clients = [];
        $clientIdType = $this->options->get(Option::CLIENT_ID_TYPE);
        foreach ($result as $client) {
            $clientId = $client->getId();
            $label = sprintf(
                '%s (%s: %s)',
                $client->getNameForView(),
                $clientIdType === Option::CLIENT_ID_TYPE_DEFAULT
                    ? $this->translator->trans('ID')
                    : $this->translator->trans('Custom ID'),
                $clientIdType === Option::CLIENT_ID_TYPE_DEFAULT
                    ? $clientId
                    : $client->getUserIdent()
            );

            if ($client->getIsLead()) {
                $clients[$leadsLabel][$clientId] = $label;
            } else {
                $clients[$activeLabel][$clientId] = $label;
            }
        }

        if ($clients[$leadsLabel] ?? []) {
            $this->sortClientsByNaturalOrder($clients[$leadsLabel]);
        }

        if ($clients[$activeLabel] ?? []) {
            $this->sortClientsByNaturalOrder($clients[$activeLabel]);
        }

        return $clients;
    }

    public function hasRelationToFinancialEntities(Client $client): bool
    {
        $invoices = $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('1')
            ->where('i.client = :client')
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($invoices) {
            return true;
        }

        $quotes = $this->entityManager->getRepository(Quote::class)
            ->createQueryBuilder('q')
            ->select('1')
            ->where('q.client = :client')
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($quotes) {
            return true;
        }

        $payments = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->select('1')
            ->where('p.client = :client')
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($payments) {
            return true;
        }

        $refunds = $this->entityManager->getRepository(Refund::class)
            ->createQueryBuilder('r')
            ->select('1')
            ->where('r.client = :client')
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return (bool) $refunds;
    }

    public function canBeConvertedToLead(Client $client): bool
    {
        // we're only interested in edits, new clients would actually throw 500 here as they don't have identifier
        if (! $client->getId()) {
            return true;
        }

        $unquotedServices = $this->entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->select('1')
            ->where('s.client = :client')
            ->andWhere('s.status != :quoted')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('client', $client)
            ->setParameter('quoted', Service::STATUS_QUOTED)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($unquotedServices) {
            return false;
        }

        $invoices = $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('1')
            ->where('i.client = :client')
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($invoices) {
            return false;
        }

        $payments = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->select('1')
            ->where('p.client = :client')
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($payments) {
            return false;
        }

        $refunds = $this->entityManager->getRepository(Refund::class)
            ->createQueryBuilder('r')
            ->select('1')
            ->where('r.client = :client')
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return ! $refunds;
    }

    public function existsAny(): bool
    {
        return (bool) $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(Client::class, 'c')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getActiveCount(): int
    {
        return (int) $this->entityManager->getRepository(Client::class)->getCountQueryBuilder()
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getLeadCount(): int
    {
        return (int) $this->entityManager->getRepository(Client::class)->getCountQueryBuilder()
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getArchiveCount(?Organization $organization = null): int
    {
        $qb = $this->entityManager->getRepository(Client::class)->getCountQueryBuilder()
            ->andWhere('c.deletedAt IS NOT NULL');

        if ($organization) {
            $qb->andWhere('c.organization = :organization')
                ->setParameter('organization', $organization);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getSuspendedCount(): int
    {
        return (int) $this->entityManager->getRepository(Client::class)->getCountQueryBuilder()
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.hasSuspendedService = TRUE')
            ->andWhere('c.isLead = false')
            ->getQuery()->getSingleScalarResult();
    }

    public function getOverdueCount(): int
    {
        return (int) $this->entityManager->getRepository(Client::class)->getCountQueryBuilder()
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->andWhere('c.hasOverdueInvoice = TRUE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Client[]
     */
    public function getClientsForRecurringInvoicesGeneration(\DateTimeInterface $nextInvoicingDay): array
    {
        /** @var Client[] $clients */
        $clients = $this->entityManager->getRepository(Client::class)->createQueryBuilder('c')
            ->select('DISTINCT c')
            ->andWhere('c.deletedAt IS NULL')
            ->join('c.services', 's')
            ->andWhere('s.deletedAt IS NULL OR s.status = :obsolete')
            ->andWhere('s.nextInvoicingDay <= :date')
            ->andWhere(
                's.activeTo IS NULL OR s.invoicingLastPeriodEnd IS NULL OR s.invoicingLastPeriodEnd <= s.activeTo'
            )
            ->andWhere('s.status != :quoted')
            ->setParameter(':date', $nextInvoicingDay->format('Y-m-d'))
            ->setParameter(':obsolete', Service::STATUS_OBSOLETE)
            ->setParameter(':quoted', Service::STATUS_QUOTED)
            ->getQuery()->getResult();

        $clientsForRabbit = [];
        foreach ($clients as $client) {
            $canCreateDraft = false;
            foreach ($client->getNotDeletedServices() as $service) {
                if ($this->canCreateDraft($service, $nextInvoicingDay)) {
                    $canCreateDraft = true;
                }
            }

            if ($canCreateDraft) {
                $clientsForRabbit[] = $client;
            }
        }
        unset($clients);

        return $clientsForRabbit;
    }

    public function findByUsernamePassword(string $username, string $password): ?Client
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

        if (! $user || ! $this->passwordEncoder->isPasswordValid($user, $password)) {
            return null;
        }

        return $user->getClient();
    }

    private function sortClientsByNaturalOrder(&$clients): void
    {
        uasort(
            $clients,
            function ($a, $b) {
                return strnatcmp($a, $b);
            }
        );
    }

    private function canCreateDraft(Service $service, \DateTimeInterface $nextInvoicingDay): bool
    {
        $nextPeriod = Invoicing::getMaxInvoicedPeriodService($service, $nextInvoicingDay);
        if (! $nextPeriod['invoicedFrom'] || ! $nextPeriod['invoicedTo']) {
            return false;
        }

        if ($this->entityManager->getRepository(InvoiceItemService::class)->hasDraft($service)) {
            return false;
        }

        return true;
    }
}
