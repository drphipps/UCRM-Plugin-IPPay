<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\UbiquitiSSO;

use AppBundle\Exception\Base64NotValidException;
use Nette\Utils\Json;
use Symfony\Component\HttpFoundation\RequestStack;

/*
 * Decryption of cookie is ported from Ubiquity Cloud from Python:
 * https://github.com/Ubiquiti-Cloud/python-ubic_sso_user/blob/develop/ubic_sso/cookies/session.py
 */

class AuthChecker
{
    private const ENV_SSO_UBNT_AUTH_SECRET = 'SSO_UBNT_AUTH_SECRET';
    private const ENV_SSO_UBNT_ENCKEY = 'SSO_UBNT_ENCKEY';
    private const COOKIE_NAME = 'UBIC_AUTH';
    private const SEPARATOR = '|';
    private const VERSION = '~1';

    /**
     * @var RequestStack
     */
    private $request;

    public function __construct(RequestStack $request)
    {
        $this->request = $request;
    }

    public function isUserAuthenticated(): bool
    {
        $cookie = $this->request->getCurrentRequest()->cookies->get(self::COOKIE_NAME);
        $encKey = getenv(self::ENV_SSO_UBNT_ENCKEY);
        $authSecret = getenv(self::ENV_SSO_UBNT_AUTH_SECRET);

        if (! $encKey || ! $authSecret) {
            @trigger_error('Authentication environment variables are not set.', E_USER_DEPRECATED);

            return false;
        }

        if (! $cookie) {
            return false;
        }

        try {
            $decoded = $this->decode($cookie, $authSecret, $encKey);

            if ($decoded && array_key_exists('is_verified', $decoded) && array_key_exists('expiry', $decoded)) {
                return $decoded['is_verified'] && $decoded['expiry'] > time();
            }

            return false;
        } catch (\OutOfBoundsException $exception) {
            return false;
        }
    }

    private function decode(string $cookie, string $authSecret, string $encKey): array
    {
        $cookieParts = explode(
            self::SEPARATOR,
            $this->decodeBase64(
                trim($cookie, '"')
            )
        );
        $base64DecodedParts = $this->decodeBase64Array(
            $this->checkVersion($cookieParts)
        );
        $encodedMap = $this->checkSign($base64DecodedParts, $authSecret);
        $decryptedParts = $this->decrypt($encodedMap, $encKey);

        return $this->unzip($decryptedParts);
    }

    /**
     * @throws Base64NotValidException
     */
    private function decodeBase64(string $string): string
    {
        $decoded = base64_decode($string, true);

        if (! $decoded || $string !== base64_encode($decoded)) {
            throw new Base64NotValidException('String is not valid');
        }

        return $decoded;
    }

    /**
     * Checks version of cookie.
     *
     * @throws \OutOfBoundsException
     */
    private function checkVersion(array $cookieParts): array
    {
        if (array_shift($cookieParts) !== self::VERSION) {
            throw new \OutOfBoundsException('Wrong cookie version detected');
        }

        return $cookieParts;
    }

    private function decodeBase64Array(array $cookieParts): array
    {
        return array_map(
            function (string $string) {
                return $this->decodeBase64($string);
            },
            $cookieParts
        );
    }

    /**
     * Checks signature and returns array of elements needed to decrypt.
     *
     * @throws \OutOfBoundsException
     */
    private function checkSign(array $cookieParts, string $authSecret): array
    {
        [$encrypted, $salt, $enciv, $signature] = $cookieParts;

        if (hash_hmac('sha1', $encrypted . $salt, $authSecret, true) !== $signature) {
            throw new \OutOfBoundsException('Wrong signature');
        }

        return [
            'encrypted' => $encrypted,
            'salt' => $salt,
            'enciv' => $enciv,
        ];
    }

    /**
     * Decrypts encrypted part with key and validates decrypted salt against provided salt.
     *
     * @throws \OutOfBoundsException
     */
    private function decrypt(array $encodedMap, string $encKey): array
    {
        $decrypted = openssl_decrypt(
            $encodedMap['encrypted'],
            'aes-256-ctr',
            pack('H*', $encKey),
            OPENSSL_RAW_DATA,
            $encodedMap['enciv']
        );

        [$plain, $desalt, $expiry] = array_map(
            function ($string) {
                return $this->decodeBase64($string);
            },
            explode(
                self::SEPARATOR,
                $decrypted
            )
        );

        if ($encodedMap['salt'] !== $desalt) {
            throw new \OutOfBoundsException('Wrong salt');
        }

        return [
            'plain' => $plain,
            'expiry' => $expiry,
        ];
    }

    private function unzip(array $decryptedParts): array
    {
        $decodedPlain = Json::decode(zlib_decode($decryptedParts['plain']), Json::FORCE_ARRAY);
        unset($decryptedParts['plain']);

        return array_merge($decryptedParts, $decodedPlain);
    }
}
