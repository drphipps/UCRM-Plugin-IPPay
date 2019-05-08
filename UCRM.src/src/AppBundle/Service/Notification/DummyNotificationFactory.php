<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Notification;

use AppBundle\Entity\Client;
use AppBundle\Entity\Country;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Organization;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\PaymentReceiptTemplate;
use AppBundle\Entity\State;
use AppBundle\Entity\User;
use AppBundle\Service\Financial\DummyFinancialFactory;
use AppBundle\Service\NotificationFactory;
use AppBundle\Service\Payment\DummyPaymentFactory;
use AppBundle\Service\Payment\PaymentReceiptTemplateRenderer;
use AppBundle\Service\PublicUrlGenerator;
use AppBundle\Util\Formatter;
use AppBundle\Util\Notification;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Faker\Generator;

class DummyNotificationFactory
{
    /**
     * @var DummyFinancialFactory
     */
    private $dummyFinancialFactory;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var NotificationFactory
     */
    private $notificationFactory;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var DummyPaymentFactory
     */
    private $dummyPaymentFactory;

    /**
     * @var PaymentReceiptTemplateRenderer
     */
    private $paymentReceiptTemplateRenderer;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(
        DummyFinancialFactory $dummyFinancialFactory,
        EntityManager $em,
        NotificationFactory $notificationFactory,
        \Twig_Environment $twig,
        DummyPaymentFactory $dummyPaymentFactory,
        PaymentReceiptTemplateRenderer $paymentReceiptTemplateRenderer,
        PublicUrlGenerator $publicUrlGenerator,
        Formatter $formatter
    ) {
        $this->dummyFinancialFactory = $dummyFinancialFactory;
        $this->em = $em;
        $this->faker = Factory::create();
        $this->faker->seed();
        $this->notificationFactory = $notificationFactory;
        $this->twig = $twig;
        $this->dummyPaymentFactory = $dummyPaymentFactory;
        $this->paymentReceiptTemplateRenderer = $paymentReceiptTemplateRenderer;
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->formatter = $formatter;
    }

    public function create(NotificationTemplate $notificationTemplate): Notification
    {
        $notification = $this->notificationFactory->create();
        $organization = $this->createOrganization();
        $client = $this->createClient($organization);
        $notification->setClient($client);
        $invoices = $this->createInvoices(3);
        $notification->setInvoices($invoices);
        $notification->setQuote($this->dummyFinancialFactory->createQuote());
        $notification->addReplacement('%CREATED_COUNT%', (string) count($invoices));

        $realOrganization = $this->em->getRepository(Organization::class)->getFirstSelected();
        $receiptTemplate = $realOrganization
            ? $realOrganization->getPaymentReceiptTemplate()
            : $this->em->find(PaymentReceiptTemplate::class, PaymentReceiptTemplate::DEFAULT_TEMPLATE_ID);
        $notification->addReplacement(
            '%PAYMENT_RECEIPT%',
            $this->paymentReceiptTemplateRenderer->getPaymentReceiptHtml(
                $this->dummyPaymentFactory->createPayment(),
                $receiptTemplate
            )
        );
        $notification->setExtraCss($this->paymentReceiptTemplateRenderer->getSanitizedCss($receiptTemplate));
        $notification->addReplacement('%PAYMENT_RECEIPT_PDF%', '');

        $invoicesList = $this->createInvoicesList(3, $organization);
        $notification->addReplacement(
            '%CREATED_LIST%',
            $this->twig->render(
                'email/admin/invoices.html.twig',
                [
                    'invoices' => $invoicesList,
                ]
            )
        );

        $this->addReplacements($notification);

        $oldPaymentPlan = new PaymentPlan();
        $oldPaymentPlan->setClient($client);
        $oldPaymentPlan->setCurrency($client->getOrganization()->getCurrency());
        $oldPaymentPlan->setAmountInSmallestUnit(1500);
        $newPaymentPlan = new PaymentPlan();
        $newPaymentPlan->setClient($client);
        $newPaymentPlan->setCurrency($client->getOrganization()->getCurrency());
        $newPaymentPlan->setAmountInSmallestUnit(3000);
        $notification->setPaymentPlanChange($newPaymentPlan, $oldPaymentPlan);

        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());

        return $notification;
    }

    private function createClient(Organization $organization): Client
    {
        $client = new Client();
        $client->setId($this->faker->randomNumber());
        $client->setUserIdent($this->faker->numerify('A###B#####Z'));
        $client->setOrganization($organization);
        $client->setClientType(Client::TYPE_RESIDENTIAL);
        $client->getUser()->setFirstName($this->faker->firstName);
        $client->getUser()->setLastName($this->faker->lastName);
        $client->getUser()->setUsername($this->faker->userName);
        $client->setStreet1($this->faker->streetAddress);
        $client->setCountry($this->em->find(Country::class, 72));
        $client->setCity($this->faker->city);
        $client->setZipCode($this->faker->postcode);
        $client->setBalance(324.32);
        $client->setAccountStandingsCredit(124.24);
        $client->setAccountStandingsOutstanding(448.56);

        return $client;
    }

    private function createOrganization(): Organization
    {
        $organization = new Organization();
        $organization->setId($this->faker->randomNumber());
        $organization->setCurrency($this->em->find(Currency::class, Currency::DEFAULT_ID));

        $organization->setName($this->faker->company);
        $organization->setTaxId((string) $this->faker->numberBetween(999999999999, 9999999999999));
        $organization->setStreet1($this->faker->streetAddress);
        $organization->setStreet2($this->faker->streetAddress);
        $organization->setCity($this->faker->city);
        $organization->setCountry($this->em->find(Country::class, 249));
        $organization->setState($this->em->find(State::class, 24));
        $organization->setZipCode($this->faker->postcode);
        $organization->setEmail($this->faker->companyEmail);
        $organization->setPhone($this->faker->phoneNumber);
        $organization->setWebsite($this->faker->domainName);
        $organization->setRegistrationNumber((string) $this->faker->numberBetween(9999999, 99999999));

        return $organization;
    }

    private function createInvoices(int $number): array
    {
        $return = [];
        for ($i = 1; $i <= $number; ++$i) {
            $return[] = $this->dummyFinancialFactory->createInvoice();
        }

        return $return;
    }

    private function addReplacements(Notification $notification): void
    {
        try {
            $baseUrl = $this->publicUrlGenerator->generate('homepage');
        } catch (\Throwable $exception) {
            $baseUrl = 'http://www.example.com/';
        }

        // Note: both the key and replacement must be strings.
        $replacements = [
            '%SERVICE_NAME%' => sprintf('Service: %s', $this->faker->sentence(3)),
            '%SERVICE_TARIFF%' => 'Tariff',
            '%SERVICE_PRICE%' => (string) $this->faker->randomFloat(2, 10, 1000),
            '%SERVICE_ACTIVE_FROM%' => $this->formatter->formatDate(
                $this->faker->dateTimeBetween('-1 month', 'yesterday'),
                Formatter::DEFAULT,
                Formatter::NONE
            ),
            '%SERVICE_ACTIVE_TO%' => $this->formatter->formatDate(
                $this->faker->dateTimeBetween('tomorrow', '+1 month'),
                Formatter::DEFAULT,
                Formatter::NONE
            ),
            '%SERVICE_STOP_REASON%' => 'Payments overdue',
            '%CLIENT_IP%' => $this->faker->ipv4,
            '%CLIENT_FIRST_LOGIN_URL%' => sprintf('%sfirst-login/1024/78572cb37de3e30ce4c43670c51c7f16', $baseUrl),
            '%CLIENT_RESET_PASSWORD_URL%' => sprintf('%sreset-password/do-reset/b89c4d341a6bb128a0f1a8196f081966', $baseUrl),
            '%ONLINE_PAYMENT_LINK%' => sprintf('%sonline-payment/pay/9a32e3c40240ec624b96b3daa2587bd2', $baseUrl),
            '%TICKET_ID%' => '20318',
            '%TICKET_STATUS%' => 'New',
            '%TICKET_SUBJECT%' => 'internet is not working',
            '%TICKET_MESSAGE%' => 'I cannot connect to my WiFi network.',
            '%TICKET_URL%' => 'http://www.example.com/client-zone/support/ticket/20318',
            '%TICKET_COMMENT_ATTACHMENTS_COUNT%' => (string) $this->faker->numberBetween(1, 3),
            '%PAYMENT_PLAN_PROVIDER%' => PaymentPlan::PROVIDER_NAMES[PaymentPlan::PROVIDER_PAYPAL],
            '%TICKET_EMAIL_FOOTER%' => $notification->getTicketEmailFooter(20318),
        ];

        foreach ($replacements as $key => $replacement) {
            $notification->addReplacement($key, $replacement);
        }
    }

    private function createInvoicesList(int $number, Organization $organization): array
    {
        $invoices = [];

        for ($i = 1; $i <= $number; ++$i) {
            $invoice = new Invoice();

            $createdDate = $this->faker->dateTimeBetween('-1 year', 'today');

            $invoice->setTotal($this->faker->randomFloat(2, 10, 1000));
            $invoice->setAmountPaid(0);
            $invoice->setOrganization($organization);

            $user = new User();
            $user->setFirstName($this->faker->firstName);
            $user->setLastName($this->faker->lastName);
            $client = new Client();
            $client->setClientType(Client::TYPE_RESIDENTIAL);
            $client->setUser($user);
            $client->setOrganization($organization);
            $invoice->setClient($client);
            $invoice->setCreatedDate($createdDate);
            $currency = new Currency();
            $currency->setCode($this->em->find(Currency::class, Currency::DEFAULT_ID)->getCode());
            $invoice->setCurrency($currency);
            $invoice->setDueDate(
                (clone $createdDate)->modify(
                    sprintf(
                        '+%d days',
                        $this->faker->numberBetween(1, 365)
                    )
                )
            );
            $invoice->setId($this->faker->randomNumber());
            $invoices[] = $invoice;
        }

        return $invoices;
    }
}
