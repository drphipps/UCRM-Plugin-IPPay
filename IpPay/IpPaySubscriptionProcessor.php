<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\IpPay;

use AppBundle\Entity\General;
use AppBundle\Entity\Organization;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class IpPaySubscriptionProcessor
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var IpPayPaymentHandler
     */
    private $ipPayPaymentHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EntityManager $em,
        Options $options,
        PaymentFacade $paymentFacade,
        IpPayPaymentHandler $ipPayPaymentHandler,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->options = $options;
        $this->paymentFacade = $paymentFacade;
        $this->ipPayPaymentHandler = $ipPayPaymentHandler;
        $this->logger = $logger;
    }

    public function process()
    {
        $this->logger->info('Starting IPPay subscription processor.');

        $isSandbox = (bool) $this->options->getGeneral(General::SANDBOX_MODE);
        $organizations = $this->em->getRepository(Organization::class)->findAll();

        foreach ($organizations as $organization) {
            if (! $organization->getIpPayUrl($isSandbox) || ! $organization->getIpPayTerminalId($isSandbox)) {
                continue;
            }

            $this->logger->info(sprintf('Processing organization ID %d.', $organization->getId()));

            $this->processOrganization($organization);
        }

        $this->logger->info('Done processing IPPay subscriptions.');
    }

    private function processOrganization(Organization $organization): void
    {
        $paymentPlans = $this->em
            ->getRepository(PaymentPlan::class)
            ->findSubscriptionsForNextPayment(
                PaymentPlan::PROVIDER_IPPAY,
                $organization,
                new \DateTime('today midnight')
            );

        foreach ($paymentPlans as $paymentPlan) {
            $this->ipPayPaymentHandler->processNextPayment($paymentPlan);
        }
    }
}
