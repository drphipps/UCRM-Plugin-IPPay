<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Entity\UserAuthenticationKey;
use AppBundle\Entity\User;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Service\PublicUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Factory\QrCodeFactory;

class MobileAppDataProvider
{
    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var QrCodeFactory
     */
    private $qrCodeFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        PublicUrlGenerator $publicUrlGenerator,
        QrCodeFactory $qrCodeFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->qrCodeFactory = $qrCodeFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * Generates QR code with UCRM mobile app connect URL.
     * Used for initial mobile app setup.
     * The "ucrm://com.ubnt.ucrm" URL can be caught by the app when QR code is scanned with 3rd party app.
     */
    public function getConnectQrCode(User $user): ?string
    {
        try {
            $url = $this->publicUrlGenerator->generate('homepage');
        } catch (PublicUrlGeneratorException $exception) {
            return null;
        }

        $qrContent = sprintf(
            'ucrm://com.ubnt.ucrm?url=%s&username=%s',
            rawurlencode($url),
            rawurlencode($user->getUsername())
        );

        return $this->qrCodeFactory->create($qrContent)->writeDataUri();
    }

    /**
     * @return array|UserAuthenticationKey[]
     */
    public function getUserAuthenticationKeys(User $user): array
    {
        $keys = $this->entityManager->getRepository(UserAuthenticationKey::class)->findBy(
            [
                'user' => $user,
            ]
        );

        return array_filter(
            $keys,
            function (UserAuthenticationKey $authenticationKey) {
                return ! $authenticationKey->isExpired();
            }
        );
    }
}
