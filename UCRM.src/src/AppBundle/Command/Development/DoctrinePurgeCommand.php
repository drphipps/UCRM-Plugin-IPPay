<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command\Development;

use AppBundle\Entity\ClientLogsView;
use AppBundle\Entity\ContactType;
use AppBundle\Entity\Country;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Financial\AccountStatementTemplate;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Entity\General;
use AppBundle\Entity\Locale;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\PaymentProvider;
use AppBundle\Entity\PaymentReceiptTemplate;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Entity\State;
use AppBundle\Entity\Timezone;
use AppBundle\Entity\UserGroup;
use AppBundle\Entity\UserGroupPermission;
use AppBundle\Entity\UserGroupSpecialPermission;
use AppBundle\Entity\Vendor;
use AppBundle\Entity\WebhookEventType;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrinePurgeCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('crm:development:doctrine:purge')
            ->setDescription('Purges Doctrine data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get(EntityManager::class);

        $excludedEntities = [
            ClientLogsView::class,
            Country::class,
            Currency::class,
            General::class,
            Locale::class,
            NotificationTemplate::class,
            Option::class,
            PaymentProvider::class,
            ServiceStopReason::class,
            State::class,
            Timezone::class,
            UserGroup::class,
            UserGroupPermission::class,
            UserGroupSpecialPermission::class,
            Vendor::class,
            InvoiceTemplate::class,
            ContactType::class,
            QuoteTemplate::class,
            PaymentReceiptTemplate::class,
            AccountStatementTemplate::class,
            WebhookEventType::class,
            ProformaInvoiceTemplate::class,
        ];

        $purger = new ORMPurger(
            $em,
            array_map(
                function (string $entity) use ($em): string {
                    return $em->getClassMetadata($entity)->getTableName();
                },
                $excludedEntities
            )
        );

        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);

        $output->writeln('Purging doctrine data.');

        $purger->purge();
    }
}
