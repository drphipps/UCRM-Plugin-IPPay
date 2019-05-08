<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Password;

use AppBundle\Entity\Device;
use AppBundle\Entity\Option;
use AppBundle\Service\Encryption;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class Encrypt
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Encryption
     */
    private $encryption;

    public function __construct(EntityManager $em, LoggerInterface $logger, Encryption $encryption)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->encryption = $encryption;
    }

    public function encrypt(): bool
    {
        if ($this->encryption->doesKeyExist()) {
            $this->logger->info('Data already encrypted.');

            return false;
        }

        $this->encryptMailerPassword();
        $this->encryptDevicePasswords();
        $this->logger->info('Data encrypted.');
        $this->em->flush();

        return true;
    }

    private function encryptMailerPassword()
    {
        $mailerPassword = $this->em->getRepository(Option::class)->findOneBy(
            [
                'code' => Option::MAILER_PASSWORD,
            ]
        );

        $plainPassword = $mailerPassword ? $mailerPassword->getValue() : null;
        if ($plainPassword) {
            $mailerPassword->setValue($this->encryption->encrypt($plainPassword));
        }
    }

    private function encryptDevicePasswords()
    {
        $deviceRepository = $this->em->getRepository(Device::class);
        $devices = $deviceRepository->findAll();
        foreach ($devices as $device) {
            if ($device->getLoginPassword()) {
                $device->setLoginPassword($this->encryption->encrypt($device->getLoginPassword()));
            }
        }
    }
}
