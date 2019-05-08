<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Csv\EntityCsvFactory\ClientCsvFactory;
use AppBundle\Component\Geocoder\Geocoder;
use AppBundle\Component\Geocoder\Google\GoogleGeocodingException;
use AppBundle\Component\HeaderNotification\HeaderNotifier;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\Download;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\User;
use AppBundle\Event\Client\ClientAddEvent;
use AppBundle\Event\Client\ClientArchiveEvent;
use AppBundle\Event\Client\ClientDeleteEvent;
use AppBundle\Event\Client\ClientEditEvent;
use AppBundle\Event\Service\ServiceArchiveEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\Service\ServiceEndEvent;
use AppBundle\Event\User\UserArchiveEvent;
use AppBundle\Exception\SequenceException;
use AppBundle\Facade\Exception\CannotCancelClientSubscriptionException;
use AppBundle\Facade\Exception\CannotDeleteDemoClientException;
use AppBundle\Facade\Exception\ClientNotDeletedExceptionInterface;
use AppBundle\RabbitMq\Client\ExportClientsMessage;
use AppBundle\RabbitMq\ClientDelete\ClientDeleteMessage;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\Client\ClientAverageMonthlyPaymentCalculator;
use AppBundle\Service\DownloadFinisher;
use AppBundle\Util\Arrays;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Nette\Utils\Random;
use RabbitMqBundle\RabbitMqEnqueuer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionDispatcher;

class ClientFacade
{
    public const DEMO_CLIENT_USERNAME = 'client';
    private const CLIENT_SEQ_ID = 'client_client_id_seq';

    /**
     * @var ActionLogger
     */
    private $actionLogger;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var PaymentPlanFacade
     */
    private $paymentPlanFacade;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var DownloadFinisher
     */
    private $downloadFinisher;

    /**
     * @var ClientCsvFactory
     */
    private $clientCsvFactory;

    /**
     * @var CustomAttributeDataProvider
     */
    private $customAttributeDataProvider;

    /**
     * @var ClientAverageMonthlyPaymentCalculator
     */
    private $clientAverageMonthlyPaymentCalculator;

    /**
     * @var ClientBankAccountFacade
     */
    private $clientBankAccountFacade;

    /**
     * @var HeaderNotifier
     */
    private $headerNotifier;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Geocoder
     */
    private $geocoder;

    /**
     * @todo Remove dependence on other facades. See UCRM-242 at YouTrack
     */
    public function __construct(
        ActionLogger $actionLogger,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        PaymentPlanFacade $paymentPlanFacade,
        TransactionDispatcher $transactionDispatcher,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        DownloadFinisher $downloadFinisher,
        ClientCsvFactory $clientCsvFactory,
        CustomAttributeDataProvider $customAttributeDataProvider,
        ClientAverageMonthlyPaymentCalculator $clientAverageMonthlyPaymentCalculator,
        ClientBankAccountFacade $clientBankAccountFacade,
        HeaderNotifier $headerNotifier,
        TranslatorInterface $translator,
        RouterInterface $router,
        Geocoder $geocoder
    ) {
        $this->actionLogger = $actionLogger;
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->paymentPlanFacade = $paymentPlanFacade;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->downloadFinisher = $downloadFinisher;
        $this->clientCsvFactory = $clientCsvFactory;
        $this->customAttributeDataProvider = $customAttributeDataProvider;
        $this->clientAverageMonthlyPaymentCalculator = $clientAverageMonthlyPaymentCalculator;
        $this->clientBankAccountFacade = $clientBankAccountFacade;
        $this->headerNotifier = $headerNotifier;
        $this->translator = $translator;
        $this->router = $router;
        $this->geocoder = $geocoder;
    }

    public function finishCsvExport(int $downloadId, array $clientIds): bool
    {
        $clients = $this->entityManager->getRepository(Client::class)->getAllQueryBuilder()
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $clientIds)
            ->getQuery()
            ->execute();

        Arrays::sortByArray($clients, $clientIds, '[c_id]');

        $customAttributes = $this->customAttributeDataProvider->getAll();

        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export.csv',
            function () use ($clients, $customAttributes) {
                return $this->clientCsvFactory->create($clients, $customAttributes);
            }
        );
    }

    public function handleCreate(Client $client): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $em) use ($client) {
                $newPassword = $client->getUser()->getPlainPassword();
                if (null !== $newPassword && $newPassword !== '') {
                    $client->getUser()->setIsActive(true);
                } else {
                    $newPassword = Random::generate(10);
                }

                $newPasswordHash = $this->passwordEncoder->encodePassword(
                    $client->getUser(),
                    $newPassword
                );

                $client->getUser()->setPassword($newPasswordHash);
                $client->resetDataByType();
                $em->persist($client);

                yield new ClientAddEvent($client);
            }
        );
    }

    public function handleUpdate(Client $client, Client $clientBeforeUpdate): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($client, $clientBeforeUpdate) {
                if (Helpers::isDemo() && $clientBeforeUpdate->getUser()->getUsername() === self::DEMO_CLIENT_USERNAME) {
                    $client->getUser()->setUsername(self::DEMO_CLIENT_USERNAME);
                }

                if (! Helpers::isDemo()) {
                    $newPassword = $client->getUser()->getPlainPassword();
                    if (null !== $newPassword) {
                        $newPasswordHash = '' === $newPassword
                            ? ''
                            : $this->passwordEncoder->encodePassword(
                                $client->getUser(),
                                $newPassword
                            );

                        $client->getUser()->setPassword($newPasswordHash);
                        $client->getUser()->setIsActive($newPasswordHash !== '');
                    }
                }

                $client->resetDataByType();

                yield new ClientEditEvent($client, $clientBeforeUpdate);
            }
        );
    }

    public function handleSwitchLead(Client $client, bool $isLead): void
    {
        if ($client->getIsLead() === $isLead) {
            return;
        }

        $clientBeforeUpdate = clone $client;
        $client->setIsLead($isLead);

        $this->headerNotifier->sendToAllAdmins(
            HeaderNotification::TYPE_INFO,
            $this->translator->trans('Client lead was activated.'),
            $this->translator->trans(
                'Client lead %clientName% has been converted to regular client.',
                [
                    '%clientName%' => $client->getNameForView(),
                ]
            ),
            $this->router->generate(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            )
        );

        $this->handleUpdate($client, $clientBeforeUpdate);
    }

    public function handleArchive(Client $client): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($client) {
                yield from $this->setArchived($client);
            }
        );
    }

    public function handleArchiveMultiple(array $ids): void
    {
        // In DESC order to improve UX, default grid order is ID DESC, so the user will be able to
        // see something is happening on refresh.
        $clients = $this->entityManager->getRepository(Client::class)->findBy(
            [
                'id' => $ids,
            ],
            [
                'id' => 'DESC',
            ]
        );

        $count = count($clients);
        foreach ($clients as $client) {
            if ($count === 1) {
                $this->handleArchive($client);
            } else {
                $this->rabbitMqEnqueuer->enqueue(
                    new ClientDeleteMessage($client->getId(), ClientDeleteMessage::OPERATION_ARCHIVE)
                );
            }
        }
    }

    /**
     * @throws ClientNotDeletedExceptionInterface
     */
    public function handleDelete(Client $client): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($client) {
                $clientId = $client->getId();
                yield from $this->setDeleted($client);
                yield new ClientDeleteEvent($client, $clientId);
            }
        );
    }

    public function handleDeleteMultiple(array $ids): void
    {
        // In DESC order to improve UX, default grid order is ID DESC, so the user will be able to
        // see something is happening on refresh.
        $clients = $this->entityManager->getRepository(Client::class)->findBy(
            [
                'id' => $ids,
            ],
            [
                'id' => 'DESC',
            ]
        );

        $count = count($clients);
        foreach ($clients as $client) {
            if ($count === 1) {
                $this->handleDelete($client);
            } else {
                $this->rabbitMqEnqueuer->enqueue(
                    new ClientDeleteMessage($client->getId(), ClientDeleteMessage::OPERATION_DELETE)
                );
            }
        }
    }

    public function handleInvitationEmailSent(Client $client): void
    {
        $client->setInvitationEmailSentDate(new \DateTime());
        $client->setInvitationEmailSendStatus(Client::INVITATION_EMAIL_SEND_STATUS_SENT);
        $this->entityManager->flush();
    }

    public function prepareCsvDownload(string $name, array $ids, User $user): void
    {
        $download = new Download();

        $this->entityManager->transactional(
            function () use ($download, $name, $user) {
                $download->setName($name);
                $download->setCreated(new \DateTime());
                $download->setStatus(Download::STATUS_PENDING);
                $download->setUser($user);

                $this->entityManager->persist($download);
            }
        );

        $this->rabbitMqEnqueuer->enqueue(
            new ExportClientsMessage(
                $download,
                $ids,
                ExportClientsMessage::FORMAT_CSV
            )
        );
    }

    public function setNextClientId(int $clientIdNext): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            if ($this->entityManager->getRepository(Client::class)->getMaxClientId() > $clientIdNext) {
                throw new SequenceException('New client ID is lower than max client ID.');
            }

            $this->entityManager->createNativeQuery(
                'ALTER SEQUENCE ' . self::CLIENT_SEQ_ID . ' RESTART WITH ' . $clientIdNext,
                new ResultSetMapping()
            )->execute();

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function updateAverageMonthlyPaymentAllClients(): void
    {
        $clients = $this->entityManager->getRepository(Client::class)->findAll();

        $this->entityManager->transactional(
            function () use ($clients) {
                foreach ($clients as $client) {
                    $this->clientAverageMonthlyPaymentCalculator->calculate($client);
                }
            }
        );
    }

    private function setArchived(Client $client): \Generator
    {
        if (Helpers::isDemo() && $client->getUser()->getUsername() === self::DEMO_CLIENT_USERNAME) {
            return;
        }

        foreach ($client->getNotDeletedServices() as $service) {
            $activeToLimit = new \DateTime('-1 day midnight');
            if ($service->getActiveTo() && $service->getActiveTo() <= $activeToLimit) {
                continue;
            }

            $oldService = clone $service;
            $service->setActiveTo($activeToLimit);

            yield new ServiceEditEvent($service, $oldService);
            yield new ServiceEndEvent($service);
        }

        $client->setDeletedAt(new \DateTime());

        yield new ClientArchiveEvent($client);
    }

    /**
     * @throws ClientNotDeletedExceptionInterface
     */
    private function setDeleted(Client $client): \Generator
    {
        $user = $client->getUser();

        if (Helpers::isDemo() && $user->getUsername() === self::DEMO_CLIENT_USERNAME) {
            throw new CannotDeleteDemoClientException($client);
        }

        // The Client entity needs to be scheduled for removal to prevent EntityLog errors.
        $this->entityManager->remove($client);
        $user->setDeletedAt(new \DateTime());

        // This should not be needed as the payment plans are cancelled during client archiving.
        // However if the queue did not yet finish, we need to cancel them all NOW,
        // as the client entity is needed for it.
        foreach ($client->getActivePaymentPlans() as $paymentPlan) {
            try {
                $this->paymentPlanFacade->cancelSubscription($paymentPlan, false);
                $message['logMsg'] = [
                    'message' => 'Subscription %s was canceled',
                    'replacements' => $paymentPlan->getName(),
                ];

                $this->actionLogger->log(
                    $message,
                    $user,
                    null, // client cannot be here, as it's being deleted now and the EntityLog would fail on cascade
                    EntityLog::PAYMENT_PLAN_CANCELED
                );
            } catch (\Exception $exception) {
                throw new CannotCancelClientSubscriptionException(
                    $paymentPlan,
                    $exception->getMessage(),
                    $exception->getCode(),
                    $exception
                );
            }
        }

        foreach ($client->getBankAccounts() as $bankAccount) {
            $this->clientBankAccountFacade->deleteCustomerFromStripe($bankAccount, true);
        }

        foreach ($client->getBankAccounts() as $bankAccount) {
            $this->clientBankAccountFacade->deleteCustomerFromStripe($bankAccount, true);
        }

        foreach ($client->getNotDeletedServices() as $service) {
            $activeToLimit = new \DateTime('-1 day midnight');
            if (! $service->getActiveTo() || $service->getActiveTo() > $activeToLimit) {
                $service->setActiveTo($activeToLimit);
            }

            $service->setDeletedAt(new \DateTime());

            yield new ServiceArchiveEvent($service);
        }

        yield new UserArchiveEvent($user);
    }

    public function getAllClients(): array
    {
        $repository = $this->entityManager->getRepository(Client::class);

        return $repository->findBy(
            [
                'deletedAt' => null,
            ],
            [
                'id' => 'ASC',
            ]
        );
    }

    /**
     * @throws GoogleGeocodingException
     * @throws \RuntimeException
     */
    public function geocode(Client $client): void
    {
        if (! $location = $this->geocoder->geocodeAddress($client->getAddressForGeocoding())) {
            throw new \RuntimeException('Could not geocode client\'s address.');
        }

        $clientBeforeUpdate = clone $client;
        $client->setAddressGpsLat($location->lat);
        $client->setAddressGpsLon($location->lon);

        $this->handleUpdate($client, $clientBeforeUpdate);
    }

    public function handleAddContact(Client $client, ClientContact $contact): void
    {
        $clientBeforeUpdate = clone $client;
        $client->addContact($contact);

        $this->handleUpdate(
            $client,
            $clientBeforeUpdate
        );
    }
}
