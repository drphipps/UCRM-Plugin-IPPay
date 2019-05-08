<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Version;

use AppBundle\Component\HeaderNotification\HeaderNotifier;
use AppBundle\Entity\General;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Option;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Yaml\Yaml;

class Bump
{
    private const FIX_UNMATCHED_PAYMENTS_VERSION_GTE = '2.3.0-beta1';
    private const FIX_UNMATCHED_PAYMENTS_VERSION_LTE = '2.3.0-beta3';
    private const FIX_RECREATED_CREDIT_VERSION_GTE = '2.3.0-beta1';
    private const FIX_RECREATED_CREDIT_VERSION_LTE = '2.5.0-beta2';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $ucrmProductPageUrl;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    /**
     * @var HeaderNotifier
     */
    private $headerNotifier;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        string $rootDir,
        string $version,
        string $ucrmProductPageUrl,
        Options $options,
        PaymentFacade $paymentFacade,
        OptionsFacade $optionsFacade,
        HeaderNotifier $headerNotifier,
        TranslatorInterface $translator
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->rootDir = $rootDir;
        $this->version = $version;
        $this->ucrmProductPageUrl = $ucrmProductPageUrl;
        $this->options = $options;
        $this->paymentFacade = $paymentFacade;
        $this->optionsFacade = $optionsFacade;
        $this->headerNotifier = $headerNotifier;
        $this->translator = $translator;
    }

    public function saveVersionToDatabase(?string $newVersion): void
    {
        $version = $newVersion ?? $this->version;
        $previousVersion = (string) $this->options->getGeneral(General::CRM_INSTALLED_VERSION, '');

        if (version_compare($version, $previousVersion) === 0) {
            $this->logger->info('DB version not updated.');

            return;
        }

        if (
            version_compare($previousVersion, self::FIX_UNMATCHED_PAYMENTS_VERSION_GTE, '>=')
            && version_compare($previousVersion, self::FIX_UNMATCHED_PAYMENTS_VERSION_LTE, '<=')
        ) {
            $this->paymentFacade->fixWronglyUnmatchedPayments();
        }

        if (
            version_compare($previousVersion, self::FIX_RECREATED_CREDIT_VERSION_GTE, '>=')
            && version_compare($previousVersion, self::FIX_RECREATED_CREDIT_VERSION_LTE, '<=')
        ) {
            $this->paymentFacade->fixWronglyCreatedCredit();
        }

        $this->optionsFacade->updateGeneral(General::CRM_INSTALLED_VERSION, $version);
        $this->logger->info(sprintf('DB version updated from %s to %s.', $previousVersion, $version));
        $this->entityManager->flush();

        $this->notifyAllAdmins($version, $previousVersion);
    }

    /**
     * For developing only.
     */
    public function saveVersionToYml(string $newVersion): void
    {
        if (version_compare($newVersion, '2.0.0') < 0) {
            $this->logger->info('Version not updated.');

            return;
        }

        $path = sprintf('%s/../app/config/version.yml', $this->rootDir);

        $result = Yaml::parse(file_get_contents($path));

        $oldStability = $result['parameters']['version_stability'];
        if (Strings::contains($newVersion, 'beta')) {
            $newStability = Option::UPDATE_CHANNEL_BETA;
        } elseif (Strings::contains($newVersion, 'dev')) {
            $newStability = Option::UPDATE_CHANNEL_BETA;
        } else {
            $newStability = Option::UPDATE_CHANNEL_STABLE;
        }
        $result['parameters']['version_stability'] = $newStability;

        $oldVersion = $result['parameters']['version'];
        $result['parameters']['version'] = $newVersion;

        file_put_contents($path, Yaml::dump($result));

        $this->logger->info(
            sprintf(
                'Bumped version from <fg=yellow>%s (%s)</> to <fg=green>%s (%s)</>',
                $oldVersion,
                $oldStability,
                $newVersion,
                $newStability
            )
        );
    }

    private function notifyAllAdmins(string $version, string $previousVersion): void
    {
        // New installation
        if ($previousVersion === '2.0.3') {
            return;
        }

        $wordJoiner = html_entity_decode('&#8288;');

        $this->headerNotifier->sendToAllAdmins(
            HeaderNotification::TYPE_INFO,
            $this->translator->trans(
                'UCRM has been updated to %version%.',
                [
                    '%version%' => Strings::replace($version, '/-/', $wordJoiner . '-' . $wordJoiner),
                ]
            ),
            $this->translator->trans('See what\'s new in this version and let us know how you like it.'),
            sprintf('%schangelog/%s', $this->ucrmProductPageUrl, $version),
            true
        );
    }
}
