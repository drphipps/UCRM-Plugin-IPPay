<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Handler;

use AppBundle\FileManager\WebrootFileManager;
use AppBundle\Service\DownloadResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class WebrootHandler
{
    /**
     * @var WebrootFileManager
     */
    private $webrootFileManager;

    /**
     * @var DownloadResponseFactory
     */
    private $downloadResponseFactory;

    /**
     * @var UrlMatcherInterface
     */
    private $urlMatcher;

    public function __construct(
        WebrootFileManager $webrootFileManager,
        DownloadResponseFactory $downloadResponseFactory,
        UrlMatcherInterface $urlMatcher
    ) {
        $this->webrootFileManager = $webrootFileManager;
        $this->downloadResponseFactory = $downloadResponseFactory;
        $this->urlMatcher = $urlMatcher;
    }

    public function downloadWebrootFile(Request $request): ?Response
    {
        if (! $this->isWebrootFile($request)) {
            return null;
        }

        return $this->downloadResponseFactory->createFromFile(
            $this->webrootFileManager->getFilePathIfExists($request->getRequestUri())
        );
    }

    /**
     * Checks if the request is for the webroot file download.
     *
     * Webroot file download is possible only for GET requests
     * to not-existing routes. Also the file must exist on the server.
     */
    private function isWebrootFile(Request $request): bool
    {
        if ($request->getMethod() !== 'GET') {
            return false;
        }

        $requestUri = $request->getRequestUri();
        try {
            $this->urlMatcher->match($requestUri);

            return false;
        } catch (ResourceNotFoundException | MethodNotAllowedException $exception) {
        }

        return null !== $this->webrootFileManager->getFilePathIfExists($requestUri);
    }
}
