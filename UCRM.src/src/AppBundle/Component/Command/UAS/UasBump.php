<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\UAS;

use AppBundle\Entity\General;
use AppBundle\Facade\OptionsFacade;
use Psr\Log\LoggerInterface;

class UasBump
{
    private const UAS_INSTALLATION_ENV = 'UAS_INSTALLATION';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    public function __construct(LoggerInterface $logger, OptionsFacade $optionsFacade)
    {
        $this->logger = $logger;
        $this->optionsFacade = $optionsFacade;
    }

    public function update(): void
    {
        $uasInstallationEnv = getenv(self::UAS_INSTALLATION_ENV);
        $uasInstallation = $uasInstallationEnv ? (trim($uasInstallationEnv) ?: null) : null;
        $this->optionsFacade->updateGeneral(General::UAS_INSTALLATION, $uasInstallation);
    }
}
