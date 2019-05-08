<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadResponseFactory
{
    public function createFromContent(
        string $content,
        string $filename,
        ?string $fileExtension = null,
        string $contentType = 'application/octet-stream',
        ?int $contentLength = null
    ): Response {
        $response = new Response($content);

        if ($fileExtension) {
            $filename = sprintf(
                '%s-%s.%s',
                $filename,
                date('YmdHis'),
                ltrim($fileExtension, '.')
            );
        }

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        if (null !== $contentLength) {
            $response->headers->set('Content-Length', (string) $contentLength);
        }

        return $response;
    }

    /**
     * @param \SplFileInfo|string $file
     *
     * @throws NotFoundHttpException
     */
    public function createFromFile(
        $file,
        string $filename = '',
        ?string $contentType = null
    ): BinaryFileResponse {
        try {
            $response = new BinaryFileResponse($file);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        if (null !== $contentType) {
            $response->headers->set('Content-Type', $contentType);
        }

        return $response;
    }
}
