<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\CertificateDataProvider;
use AppBundle\Entity\EntityLog;
use AppBundle\Facade\CertificateFacade;
use AppBundle\Form\CertificateUploadType;
use AppBundle\Form\Data\CertificateUploadData;
use AppBundle\Form\Data\LetsEncryptData;
use AppBundle\Form\LetsEncryptType;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Util\Helpers;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/tools/ssl-certificate")
 */
class CertificateController extends BaseController
{
    /**
     * @Route("", name="certificate_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="SSL certificate", path="System -> Tools -> SSL certificate")
     */
    public function indexAction(Request $request): Response
    {
        $certificateUpload = new CertificateUploadData();
        $certificateUploadForm = $this->container->get('form.factory')
            ->createNamedBuilder(
                'certificateUploadForm',
                CertificateUploadType::class,
                $certificateUpload
            )
            ->getForm();

        $certificateDataProvider = $this->get(CertificateDataProvider::class);
        $letsEncrypt = new LetsEncryptData();
        $letsEncrypt->email = $certificateDataProvider->getLetsEncryptEmail();
        $letsEncryptForm = $this->container->get('form.factory')
            ->createNamedBuilder(
                'letsEncryptForm',
                LetsEncryptType::class,
                $letsEncrypt
            )
            ->getForm();

        $certificateUploadForm->handleRequest($request);
        $letsEncryptForm->handleRequest($request);
        if ($certificateUploadForm->isSubmitted() || $letsEncryptForm->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($certificateUploadForm->isSubmitted()) {
            if (Helpers::isDemo()) {
                return $this->redirectToRoute('certificate_index');
            }

            $disableButton = $certificateUploadForm->get('disableButton');
            if ($disableButton->isClicked()) {
                return $this->handleDisableCertificates();
            }

            if ($certificateDataProvider->isLetsEncryptEnabled()) {
                $this->addTranslatedFlash(
                    'danger',
                    'Custom certificate can\'t be used when Let\'s Encrypt is enabled. Disable Let\'s Encrypt first.'
                );

                return $this->redirectToRoute('certificate_index');
            }

            if ($certificateUploadForm->isValid()) {
                return $this->handleCertificateUpload($certificateUpload);
            }
        }

        if ($letsEncryptForm->isSubmitted()) {
            if (Helpers::isDemo()) {
                return $this->redirectToRoute('certificate_index');
            }

            $disableButton = $letsEncryptForm->get('disableButton');
            if ($disableButton->isClicked()) {
                return $this->handleDisableLetsEncrypt();
            }

            if ($letsEncryptForm->isValid()) {
                return $this->handleEnableLetsEncrypt($letsEncrypt);
            }
        }

        return $this->render(
            'certificate/index.html.twig',
            [
                'certificateUploadForm' => $certificateUploadForm->createView(),
                'letsEncryptForm' => $letsEncryptForm->createView(),
                'isLetsEncryptEnabled' => $certificateDataProvider->isLetsEncryptEnabled(),
                'isCustomCertificateEnabled' => $certificateDataProvider->isCustomEnabled(),
                'serverControlLog' => $certificateDataProvider->getServerControlLog(),
                'letsEncryptLog' => $certificateDataProvider->getLetsEncryptLog(),
                'customExpiration' => $certificateDataProvider->getCustomExpiration(),
                'letsEncryptExpiration' => $certificateDataProvider->getLetsEncryptExpiration(),
            ]
        );
    }

    private function handleCertificateUpload(CertificateUploadData $certificateUploadData): Response
    {
        $certificateFacade = $this->get(CertificateFacade::class);
        if (! $certificateFacade->handleCustomUpload($certificateUploadData)) {
            $this->addTranslatedFlash('error', 'Certificate files could not be uploaded.');

            return $this->redirectToRoute('certificate_index');
        }

        $message['logMsg'] = [
            'message' => 'HTTPS certificate was uploaded.',
            'replacements' => '',
        ];

        $this->get(ActionLogger::class)->log($message, $this->getUser(), null, EntityLog::CERTIFICATE_UPLOADED);

        $this->addTranslatedFlash('success', 'Certificate files uploaded.');
        $this->addTranslatedFlash('success', 'Server configuration will be updated in a moment.');
        $certificateFacade->enableCustom();

        return $this->redirectToRoute('certificate_index');
    }

    private function handleDisableCertificates(): Response
    {
        $this->get(CertificateFacade::class)->disableCustom();
        $this->addTranslatedFlash('success', 'Server configuration will be updated in a moment.');

        return $this->redirectToRoute('certificate_index');
    }

    private function handleEnableLetsEncrypt(LetsEncryptData $letsEncrypt): Response
    {
        $this->get(CertificateFacade::class)->enableLetsEncrypt($letsEncrypt->email);

        $this->addTranslatedFlash('success', 'Server configuration will be updated in a moment.');

        return $this->redirectToRoute('certificate_index');
    }

    private function handleDisableLetsEncrypt(): Response
    {
        $this->get(CertificateFacade::class)->disableLetsEncrypt();

        $this->addTranslatedFlash('success', 'Server configuration will be updated in a moment.');

        return $this->redirectToRoute('certificate_index');
    }
}
