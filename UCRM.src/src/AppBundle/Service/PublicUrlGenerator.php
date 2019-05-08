<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\DataProvider\CertificateDataProvider;
use AppBundle\Entity\Option;
use AppBundle\Exception\PublicUrlGeneratorException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PublicUrlGenerator
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CertificateDataProvider
     */
    private $certificateDataProvider;

    public function __construct(
        Options $options,
        RouterInterface $router,
        CertificateDataProvider $certificateDataProvider
    ) {
        $this->options = $options;
        $this->router = $router;
        $this->certificateDataProvider = $certificateDataProvider;
    }

    /**
     * Generates a publicly accessible URL from the given parameters.
     * Uses Server FQDN and Server IP options respectively, if not set throws an exception because of security.
     *
     * @throws PublicUrlGeneratorException
     */
    public function generate(string $route, array $parameters = [], bool $forceHttps = false, ?int $port = null): string
    {
        $serverFQDN = $this->options->get(Option::SERVER_FQDN);
        $serverIP = $this->options->get(Option::SERVER_IP);
        $serverPort = $this->options->get(Option::SERVER_PORT);
        $suspensionPort = $this->options->get(Option::SERVER_SUSPEND_PORT);
        // When non-standard port is used and SSL certificate is configured, we want to use HTTPS for public URLs.
        $useHttpsForPublicURL = $serverPort !== 443
            && $serverPort !== 80
            && (
                $this->certificateDataProvider->isCustomEnabled()
                || $this->certificateDataProvider->isLetsEncryptEnabled()
            );

        $absoluteUrl = $this->router->generate(
            $route,
            $parameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $url = @parse_url($absoluteUrl); // @ - is escalated to exception
        if ($url === false) {
            throw new \InvalidArgumentException("Malformed or unsupported URL '$url'.");
        }

        if ($forceHttps || $serverPort === 443 || $useHttpsForPublicURL) {
            $url['scheme'] = 'https';
        }

        // When HTTPS is not forced and the requested port is suspension port, use HTTP.
        // This is used in SuspendController, because HTTPS can't be used there.
        if (! $forceHttps && $port === $suspensionPort) {
            $url['scheme'] = 'http';
        }

        if (! $serverFQDN && ! $serverIP) {
            throw new PublicUrlGeneratorException(
                'Server domain name or server IP must be configured before public URL can be generated.'
            );
        }

        if ($serverFQDN) {
            $serverFQDN = $this->formatFQDN($serverFQDN);
        }

        $urlPath = $this->router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
        if ($forceHttps && $serverPort === 80) {
            $serverPort = 443;
        }
        $newUrl = [
            'scheme' => isset($url['scheme']) ? $url['scheme'] : ($forceHttps ? 'https' : 'http'),
            'host' => $serverFQDN ? $serverFQDN : $serverIP,
            'path' => $urlPath,
            'port' => $serverPort,
        ];

        return $this->getUrlString($newUrl, $port);
    }

    private function formatFQDN(string $fqdn): string
    {
        $url = @parse_url($fqdn); // @ - is escalated to exception
        if ($url === false) {
            throw new \InvalidArgumentException("Malformed or unsupported URL '$fqdn'.");
        }

        unset($url['scheme']);
        $url = $this->getUrlString($url, null);

        return rtrim($url, '/');
    }

    private function getUrlString(array $parse, ?int $port): string
    {
        $url = [];
        $url['scheme'] = isset($parse['scheme']) ? $parse['scheme'] . '://' : '';
        $url['host'] = isset($parse['host']) ? $parse['host'] : '';
        if ($port !== null) {
            $url['port'] = ':' . $port;
        } else {
            $url['port'] = isset($parse['port']) ? ':' . (int) $parse['port'] : '';
        }
        $url['user'] = isset($parse['user']) ? $parse['user'] : '';
        $url['pass'] = isset($parse['pass']) ? ':' . $parse['pass'] : '';
        $url['pass'] = ($url['user'] || $url['pass']) ? $url['pass'] . '@' : '';
        $url['path'] = isset($parse['path']) ? $parse['path'] : '';
        $url['query'] = isset($parse['query']) ? '?' . $parse['query'] : '';
        $url['fragment'] = isset($parse['fragment']) ? '#' . $parse['fragment'] : '';

        if (
            isset($url['port']) && (
                (in_array($url['port'], [80, ':80'], true) && $url['scheme'] === 'http://')
                || (in_array($url['port'], [443, ':443'], true) && $url['scheme'] === 'https://')
            )
        ) {
            $url['port'] = '';
        }

        return implode('', $url);
    }
}
