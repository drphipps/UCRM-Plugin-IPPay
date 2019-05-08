<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Command\Statistics\StatisticsSender;
use AppBundle\DataProvider\BackupDataProvider;
use AppBundle\DataProvider\LocaleDataProvider;
use AppBundle\Entity\Currency;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Financial\AccountStatementTemplate;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\FinancialTemplateInterface;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Entity\General;
use AppBundle\Entity\Locale;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\PaymentReceiptTemplate;
use AppBundle\Entity\Tax;
use AppBundle\Entity\Timezone;
use AppBundle\Entity\User;
use AppBundle\Exception\BackupRestoreException;
use AppBundle\Facade\BackupFacade;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Form\BackupType;
use AppBundle\Form\Data\BackupUploadData;
use AppBundle\Form\Data\Settings\WizardMailerData;
use AppBundle\Form\Data\WizardData;
use AppBundle\Form\Data\WizardFinishingData;
use AppBundle\Form\Data\WizardOrganizationData;
use AppBundle\Form\WizardFinishingType;
use AppBundle\Form\WizardMailerType;
use AppBundle\Form\WizardOrganizationType;
use AppBundle\Form\WizardType;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\Options;
use AppBundle\Service\OptionsManager;
use AppBundle\Util\Helpers;
use GuzzleHttp\Exception\GuzzleException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @Route("/wizard")
 */
class WizardController extends BaseController
{
    /**
     * @Route(name="wizard_index")
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function indexAction(): Response
    {
        if ($demo = $this->disallowInUbiquitiDemo()) {
            return $demo;
        }

        if ($requiredRedirect = $this->redirectToRequiredStepIfNotDone()) {
            return $requiredRedirect;
        }

        $this->convertWizardToSuperAdmin();

        return $this->redirectToRoute('wizard_lets_start');
    }

    /**
     * @Route("/account", name="wizard_account")
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function accountAction(Request $request): Response
    {
        if ($demo = $this->disallowInUbiquitiDemo()) {
            return $demo;
        }

        /** @var User $user */
        $user = $this->getUser();
        $formFactory = $this->container->get('form.factory');
        $optionsManager = $this->get(OptionsManager::class);

        /** @var WizardData $wizard */
        $wizard = $optionsManager->loadOptionsIntoDataClass(WizardData::class);
        $wizard->username = $user->getUsername();
        $wizard->firstName = $user->getFirstName();
        $wizard->lastName = $user->getLastName();
        $wizard->email = $user->getEmail() === WizardData::DEFAULT_EMAIL ? null : $user->getEmail();
        $wizard->locale = $this->em->getRepository(Locale::class)->findOneBy(
            [
                'code' => $wizard->localeOption === Locale::DEFAULT_CODE
                    ? $this->get(LocaleDataProvider::class)->getPreferredLocale($request)
                    : $wizard->localeOption,
            ]
        );
        $wizard->timezone = $this->em->getRepository(Timezone::class)->findOneBy(
            [
                'name' => $wizard->timezoneOption,
            ]
        );

        $accountForm = $formFactory->createNamed(
            'account_form',
            WizardType::class,
            $wizard
        );
        $accountForm->handleRequest($request);

        if ($accountForm->isSubmitted() && $accountForm->isValid()) {
            $this->em->transactional(
                function () use ($user, $wizard, $optionsManager) {
                    $password = $this->get('security.password_encoder')->encodePassword($user, $wizard->password);
                    $user->setPassword($password);
                    $user->setUsername($wizard->username);
                    $user->setEmail($wizard->email);
                    $user->setFirstName($wizard->firstName);
                    $user->setLastName($wizard->lastName);
                    $wizard->localeOption = $wizard->locale->getCode();
                    $wizard->timezoneOption = $wizard->timezone->getName();

                    $optionsManager->updateOptions($wizard);
                    $this->get(OptionsFacade::class)->updateGeneral(General::WIZARD_ACCOUNT_DONE, '1');

                    $message['logMsg'] = [
                        'message' => 'Admin\'s username and password were set via wizard.',
                        'replacements' => '',
                    ];

                    $this->get(ActionLogger::class)->log(
                        $message,
                        $user,
                        $user->getClient(),
                        EntityLog::PASSWORD_CHANGE
                    );
                }
            );

            return $this->redirectToRoute('wizard_organization');
        }

        $backupUpload = new BackupUploadData();
        $backupUploadForm = $formFactory->createNamed(
            'backup_upload_form',
            BackupType::class,
            $backupUpload
        );
        $backupUploadForm->handleRequest($request);

        if ($backupUploadForm->isSubmitted() && $backupUploadForm->isValid()) {
            try {
                $this->get(BackupFacade::class)->handleBackupUploadRestore($backupUpload->backupFile);
            } catch (BackupRestoreException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());

                return $this->redirectToRoute('wizard_account');
            }
            $this->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();

            return $this->redirectToRoute('homepage');
        }

        return $this->render(
            'wizard/account_setup.html.twig',
            [
                'showHeader' => false,
                'showLeftPanel' => false,
                'accountForm' => $accountForm->createView(),
                'backupUploadForm' => $backupUploadForm->createView(),
                'isUserWizard' => $user->getRole() === User::ROLE_WIZARD,
                'layoutHideHeader' => true,
                'stepsProgress' => $this->getStepsProgress(),
                'isRestoreInProgress' => $this->get(BackupDataProvider::class)->isUploadedBackupRestoreInProgress(),
            ]
        );
    }

    /**
     * @Route(
     *     "/account-setup/get-supported-timezone/{clientTimezone}",
     *     name="ajax_get_timezone",
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function getSupportedTimezone(string $clientTimezone): Response
    {
        $clientTimezone = urldecode($clientTimezone);
        $chosenTimezone = null;

        try {
            $timezone = new \DateTimeZone($clientTimezone);
        } catch (\Exception $e) {
            // Don't do anything if PHP doesn't recognize $clientTimezone
        }

        if (isset($timezone)) {
            $supportedTimezones = $this->em->getRepository(Timezone::class)->findAll();

            $clientTimezoneContinent = explode('/', $timezone->getName())[0];
            $clientTimezoneCountry = explode('/', $timezone->getName())[1] ?? '';

            $january = new \DateTime('January 1');
            $july = new \DateTime('July 1');

            foreach ($supportedTimezones as $supportedTimezone) {
                $supportedTimezoneContinent = explode('/', $supportedTimezone->getName())[0];
                $supportedDateTimeZone = new \DateTimeZone($supportedTimezone->getName());

                if (
                    $clientTimezoneCountry
                    && strpos($supportedTimezone->getLabel(), $clientTimezoneCountry) !== false
                ) {
                    $chosenTimezone = $supportedDateTimeZone;
                    break;
                }
                if (
                    null === $chosenTimezone &&
                    $supportedDateTimeZone->getOffset($january) === $timezone->getOffset($january) &&
                    $supportedDateTimeZone->getOffset($july) === $timezone->getOffset($july) &&
                    $supportedTimezoneContinent === $clientTimezoneContinent
                ) {
                    $chosenTimezone = $supportedDateTimeZone;
                }
            }
        }

        $json = [
            'supportedTimezone' => $chosenTimezone ? $chosenTimezone->getName() : Timezone::DEFAULT_NAME,
        ];

        return $this->createAjaxResponse($json, false);
    }

    /**
     * @Route("/organization", name="wizard_organization")
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function organizationAction(Request $request): Response
    {
        if ($demo = $this->disallowInUbiquitiDemo()) {
            return $demo;
        }

        if ($requiredRedirect = $this->redirectToRequiredStepIfNotDone(true)) {
            return $requiredRedirect;
        }

        $organization = $this->em->getRepository(Organization::class)->getFirstSelected();

        // @todo refactor to factory (UCRM-2883)
        $organization = $organization ?? new Organization();
        if (! $organization->getId()) {
            $organization->setCurrency(
                $this->em->find(Currency::class, Currency::DEFAULT_ID)
            );
            $organization->setInvoiceTemplate(
                $this->em->find(InvoiceTemplate::class, FinancialTemplateInterface::DEFAULT_TEMPLATE_ID)
            );
            $organization->setQuoteTemplate(
                $this->em->find(QuoteTemplate::class, FinancialTemplateInterface::DEFAULT_TEMPLATE_ID)
            );
            $organization->setPaymentReceiptTemplate(
                $this->em->find(PaymentReceiptTemplate::class, PaymentReceiptTemplate::DEFAULT_TEMPLATE_ID)
            );
            $organization->setAccountStatementTemplate(
                $this->em->find(AccountStatementTemplate::class, AccountStatementTemplate::DEFAULT_TEMPLATE_ID)
            );
            $organization->setProformaInvoiceTemplate(
                $this->em->find(ProformaInvoiceTemplate::class, ProformaInvoiceTemplate::DEFAULT_TEMPLATE_ID)
            );
            $organization->setInvoiceInitNumber(1);
        }

        $optionsManager = $this->get(OptionsManager::class);
        /** @var WizardOrganizationData $wizardOrganizationData */
        $wizardOrganizationData = $optionsManager->loadOptionsIntoDataClass(WizardOrganizationData::class);
        $wizardOrganizationData->organization = $organization;
        $wizardOrganizationDataBeforeSubmit = clone $wizardOrganizationData;
        $wizardOrganizationDataBeforeSubmit->organization = clone $organization;

        $hasFinancialEntities = $organization->getId()
            ? $this->get(OrganizationFacade::class)->hasRelationToFinancialEntities($organization)
            : false;

        $form = $this->createForm(
            WizardOrganizationType::class,
            $wizardOrganizationData,
            [
                'allow_invoice_item_rounding' => $wizardOrganizationData->invoiceItemRounding
                    === FinancialInterface::ITEM_ROUNDING_NO_ROUNDING,
                'hasFinancialEntities' => $hasFinancialEntities,
                'organization' => $organization,
            ]
        );
        $organizationBeforeUpdate = clone $organization;
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (
                $hasFinancialEntities
                && $organizationBeforeUpdate->getCurrency() !== $organization->getCurrency()
            ) {
                $form->get('organization.currency')->addError(
                    new FormError('Currency cannot be changed.')
                );
            }

            if ($form->isValid()) {
                if (! $organization->getId()) {
                    $organization->setSelected(true);
                }

                $this->get(OrganizationFacade::class)->handleNew($wizardOrganizationData->organization);
                $optionsManager->updateOptions($wizardOrganizationData);

                $this->convertWizardToSuperAdmin();

                return $this->redirectToRoute('wizard_mailer');
            }
        }

        return $this->render(
            'wizard/organization.html.twig',
            [
                'showHeader' => false,
                'showLeftPanel' => false,
                'organization' => $organization,
                'form' => $form->createView(),
                'layoutHideHeader' => true,
                'stepsProgress' => $this->getStepsProgress(),
                'existTaxes' => $this->em->getRepository(Tax::class)->existsAny(),
            ]
        );
    }

    /**
     * @Route("/mailer", name="wizard_mailer")
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function mailerAction(Request $request): Response
    {
        if ($demo = $this->disallowInUbiquitiDemo()) {
            return $demo;
        }

        if ($requiredRedirect = $this->redirectToRequiredStepIfNotDone()) {
            return $requiredRedirect;
        }

        $organization = $this->em->getRepository(Organization::class)->getFirstSelected();
        $optionsManager = $this->get(OptionsManager::class);
        $optionsFacade = $this->get(OptionsFacade::class);

        /** @var WizardMailerData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(WizardMailerData::class);

        if (! $options->mailerSenderAddress) {
            $options->mailerSenderAddress = $organization
                ? $organization->getEmail()
                : $this->getUser()->getEmail();
        }

        $form = $this->createForm(WizardMailerType::class, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $optionsFacade->handleUpdateMailerSettings($options);

            $optionsFacade->updateGeneral(
                General::ONBOARDING_HOMEPAGE_MAILER_VIA_WIZARD,
                $this->getStepsProgress()['mailer']['done'] ? '1' : '0'
            );

            return $this->redirectToRoute('wizard_lets_start');
        }

        return $this->render(
            'wizard/mailer.html.twig',
            [
                'form' => $form->createView(),
                'isGmail' => $options->mailerTransport === Option::MAILER_TRANSPORT_GMAIL,
                'showLeftPanel' => false,
                'layoutHideHeader' => true,
                'stepsProgress' => $this->getStepsProgress(),
                'organization' => $organization,
            ]
        );
    }

    /**
     * @Route("/lets-start", name="wizard_lets_start")
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function letsStartAction(Request $request): Response
    {
        if ($demo = $this->disallowInUbiquitiDemo()) {
            return $demo;
        }

        if ($requiredRedirect = $this->redirectToRequiredStepIfNotDone()) {
            return $requiredRedirect;
        }

        $alreadyIsSandbox = $this->isSandbox();

        $organizationIsOnlyUSA = ! $this->em->getRepository(Organization::class)->existsOtherThanUSA();

        $optionsManager = $this->get(OptionsManager::class);
        /** @var WizardFinishingData $wizard */
        $wizard = $optionsManager->loadOptionsIntoDataClass(WizardFinishingData::class);

        /** @var User $user */
        $user = $this->getUser();

        // default for anonymous statistics TRUE only for USA (GDPR)
        $wizard->sendAnonymousStatistics = $organizationIsOnlyUSA || $wizard->sendAnonymousStatistics;
        $wizard->enableDemoMode = true;

        $form = $this->createForm(WizardFinishingType::class, $wizard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->transactional(
                function () use ($wizard, $optionsManager, $alreadyIsSandbox) {
                    $optionsManager->updateOptions($wizard);

                    if (! $alreadyIsSandbox) {
                        $this->get(OptionsFacade::class)->updateGeneral(
                            General::SANDBOX_MODE,
                            $wizard->enableDemoMode ? '1' : '0'
                        );
                    }
                    try {
                        $this->get(StatisticsSender::class)->send();
                    } catch (GuzzleException $exception) {
                        // in other cases ignore, sending stats is not important enough to crash
                    }
                }
            );

            return $this->redirectToRoute('homepage');
        }

        return $this->render(
            'wizard/lets_start.html.twig',
            [
                'showHeader' => false,
                'showLeftPanel' => false,
                'layoutHideHeader' => true,
                'stepsProgress' => $this->getStepsProgress(),
                'form' => $form->createView(),
                'user' => $user,
                'alreadyIsSandbox' => $alreadyIsSandbox,
            ]
        );
    }

    /**
     * Converts wizard user to super admin and auto login with new role.
     */
    private function convertWizardToSuperAdmin(): void
    {
        $user = $this->getUser();

        if ($user->getRole() === User::ROLE_WIZARD) {
            $user = $this->em->merge($user);
            $user->setRole(User::ROLE_SUPER_ADMIN);
            $this->em->persist($user);
            $this->em->flush();
        }

        if ($user->getRole() === User::ROLE_SUPER_ADMIN) {
            // force user update with new role
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $this->get(SessionInterface::class)->set('_security_main', serialize($token));
        }
    }

    private function disallowInUbiquitiDemo(): ?RedirectResponse
    {
        return Helpers::isDemo()
            ? $this->redirectToRoute('homepage')
            : null;
    }

    private function redirectToRequiredStepIfNotDone(bool $skipOrganizationCheck = false): ?RedirectResponse
    {
        $progress = $this->getStepsProgress();
        if (! $progress['account']['done']) {
            return $this->redirectToRoute('wizard_account');
        }

        if (! $skipOrganizationCheck && ! $progress['organization']['done']) {
            return $this->redirectToRoute('wizard_organization');
        }

        return null;
    }

    private function getStepsProgress(): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $options = $this->get(Options::class);

        $progress = [
            'account' => [
                'done' => $options->getGeneral(General::WIZARD_ACCOUNT_DONE) === '1',
                'data' => [],
            ],
            'organization' => [
                'done' => $this->em->getRepository(Organization::class)->existsAny(),
                'data' => [],
            ],
            'mailer' => [
                'done' => $options->get(Option::MAILER_HOST)
                    || (
                        $options->get(Option::MAILER_TRANSPORT) === Option::MAILER_TRANSPORT_GMAIL
                        && $options->get(Option::MAILER_USERNAME)
                    ),
                'data' => [],
            ],
        ];

        if ($progress['account']['done']) {
            $locale = $this->em->getRepository(Locale::class)->findOneBy(
                [
                    'code' => $options->get(Option::APP_LOCALE),
                ]
            );
            $timezone = $this->em->getRepository(Timezone::class)->findOneBy(
                [
                    'name' => $options->get(Option::APP_TIMEZONE),
                ]
            );

            $progress['account']['data'] = [
                'name' => $user->getNameForView(),
                'email' => $user->getEmail(),
                'timezone' => $timezone->getLabel(),
                'locale' => $locale->getName(),
            ];
        }

        if (
            $progress['organization']['done']
            && $organization = $this->em->getRepository(Organization::class)->getFirstSelected()
        ) {
            $progress['organization']['data'] = [
                'name' => $organization->getName(),
                'email' => $organization->getEmail(),
                'address' => $organization->getAddress(false),
            ];
        }

        if ($progress['mailer']['done']) {
            $progress['mailer']['data'] = [
                'username' => $options->get(Option::MAILER_USERNAME),
                'host' => $options->get(Option::MAILER_HOST),
            ];
        }

        return $progress;
    }
}
