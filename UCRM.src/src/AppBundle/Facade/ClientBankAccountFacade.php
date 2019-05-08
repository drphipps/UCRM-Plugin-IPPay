<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Stripe\Customer;
use AppBundle\Component\Stripe\TokenBankAccount;
use AppBundle\Entity\ClientBankAccount;
use AppBundle\Entity\General;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ClientBankAccountFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TokenBankAccount
     */
    private $stripeTokenBankAccount;

    /**
     * @var Customer
     */
    private $stripeCustomer;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EntityManagerInterface $em,
        TokenBankAccount $stripeTokenBankAccount,
        Customer $stripeCustomer,
        Options $options,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->stripeTokenBankAccount = $stripeTokenBankAccount;
        $this->stripeCustomer = $stripeCustomer;
        $this->options = $options;
        $this->logger = $logger;
    }

    public function createStripeBankAccount(ClientBankAccount $clientBankAccount, bool $isSandbox): void
    {
        $this->clearDeletedStripeCustomer($clientBankAccount, $isSandbox);

        if (! $clientBankAccount->getStripeBankAccountToken()) {
            $this->createStripeBankAccountToken($clientBankAccount, $isSandbox);
        }

        $stripeCustomer = $this->stripeCustomer->create(
            $clientBankAccount,
            $isSandbox,
            $clientBankAccount->getStripeBankAccountToken()
        );

        $clientBankAccount->setStripeCustomerId($stripeCustomer->id);
        $this->handleEdit($clientBankAccount);
    }

    public function verifyStripeBankAccount(
        ClientBankAccount $clientBankAccount,
        bool $isSandbox,
        int $firstDeposit,
        int $secondDeposit
    ): bool {
        $result = $this->stripeTokenBankAccount->verify($clientBankAccount, $isSandbox, $firstDeposit, $secondDeposit);
        if ($result) {
            $clientBankAccount->setStripeBankAccountVerified(true);
            $this->handleEdit($clientBankAccount);
        }

        return $result;
    }

    public function handleEdit(ClientBankAccount $clientBankAccount): void
    {
        $this->em->flush();
    }

    public function handleNew(ClientBankAccount $clientBankAccount): void
    {
        $this->em->persist($clientBankAccount);
        $this->em->flush();
    }

    public function handleDelete(ClientBankAccount $clientBankAccount): void
    {
        $this->deleteCustomerFromStripe($clientBankAccount);
        $this->em->remove($clientBankAccount);
        $this->em->flush();
    }

    public function deleteCustomerFromStripe(ClientBankAccount $clientBankAccount, bool $forceDelete = false): void
    {
        $stripeCustomerId = $clientBankAccount->getStripeCustomerId();
        if (! $stripeCustomerId) {
            return;
        }

        $clientBankAccountRepository = $this->em->getRepository(ClientBankAccount::class);
        $customerBankAccounts = $clientBankAccountRepository->findBy(
            [
                'stripeCustomerId' => $stripeCustomerId,
            ]
        );

        // Only delete customer from Stripe if this is the only use of customer ID in UCRM.
        if ($forceDelete || count($customerBankAccounts) === 1) {
            $isSandbox = (bool) $this->options->getGeneral(General::SANDBOX_MODE);
            $client = $clientBankAccount->getClient();

            try {
                $stripeSecretKey = $client->getOrganization()->getStripeSecretKey($isSandbox);
                if (! $stripeSecretKey) {
                    $this->logger->error(
                        sprintf(
                            'Cannot delete Stripe customer "%s", because there is no Stripe API key available.',
                            $stripeCustomerId
                        )
                    );

                    return;
                }
                $customer = $this->stripeCustomer->retrieve(
                    $stripeSecretKey,
                    $stripeCustomerId
                );
                $customer->delete();
            } catch (\Throwable $exception) {
                $this->logger->error(sprintf('Cannot delete customer. Reason: %s', $exception->getMessage()));
            }
        }
    }

    public function clearDeletedStripeCustomer(ClientBankAccount $clientBankAccount, bool $isSandbox): void
    {
        if (! $clientBankAccount->getStripeCustomerId()) {
            return;
        }

        $client = $clientBankAccount->getClient();
        $customer = $this->stripeCustomer->retrieve(
            $client->getOrganization()->getStripeSecretKey($isSandbox),
            $clientBankAccount->getStripeCustomerId()
        );

        if ($customer->deleted) {
            $this->clearStripeClient($clientBankAccount);
        }
    }

    private function createStripeBankAccountToken(ClientBankAccount $clientBankAccount, bool $isSandbox): void
    {
        $token = $this->stripeTokenBankAccount->create($clientBankAccount, $isSandbox);
        $clientBankAccount->setStripeBankAccountToken($token->id);
        $clientBankAccount->setStripeBankAccountId($token->bank_account->id);

        $this->handleEdit($clientBankAccount);
    }

    public function clearStripeClient(ClientBankAccount $clientBankAccount): void
    {
        $clientBankAccount->setStripeCustomerId(null);
        $clientBankAccount->setStripeBankAccountToken(null);
        $clientBankAccount->setStripeBankAccountId(null);

        $this->handleEdit($clientBankAccount);
    }

    public function disconnectStripeBankAccount(string $id): void
    {
        $clientBankAccount = $this->em->getRepository(ClientBankAccount::class)->findOneBy(
            [
                'stripeBankAccountId' => $id,
            ]
        );

        if ($clientBankAccount) {
            $clientBankAccount->setStripeBankAccountVerified(false);
            $clientBankAccount->setStripeBankAccountId(null);
            $clientBankAccount->setStripeBankAccountToken(null);
            $clientBankAccount->setStripeCustomerId(null);
            $this->handleEdit($clientBankAccount);

            foreach ($clientBankAccount->getPaymentStripePendings() as $paymentStripePending) {
                $this->em->remove($paymentStripePending);
            }
            $this->em->flush();
        }
    }
}
