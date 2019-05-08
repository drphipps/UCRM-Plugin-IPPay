<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Component\Form\FormErrorDomain;
use AppBundle\Component\Import\CsvImporter;
use AppBundle\Component\Import\CustomCsvImport;
use AppBundle\Component\Import\CustomCsvImportFactory;
use AppBundle\Entity\CsvImportMapping;
use AppBundle\Facade\CsvImportMappingFacade;
use AppBundle\Form\CsvImportPaymentsType;
use AppBundle\Form\CsvImportPaymentType;
use AppBundle\Form\CsvUploadType;
use AppBundle\Form\Data\CsvUploadData;
use AppBundle\Handler\CsvImport\CsvImportHandler;
use AppBundle\Security\Permission;
use AppBundle\Util\Files;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/system/tools/import/payments")
 */
class PaymentImportController extends BaseController
{
    /**
     * @Route("", name="import_payments_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Payments import", path="System -> Tools -> Payments import")
     */
    public function indexPaymentAction(Request $request): Response
    {
        $paymentsUpload = new CsvUploadData();
        $form = $this->createForm(
            CsvUploadType::class,
            $paymentsUpload,
            [
                // needed because of Dropzone.js
                'action' => $this->generateUrl('import_payments_index'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            if (! $paymentsUpload->file) {
                $form->get('file')->addError(new FormError('Uploaded file is not valid.'));
            }

            if ($form->isValid()) {
                $fs = new Filesystem();
                $dir = $this->getImportDir();
                $fs->mkdir($dir);

                $filename = sprintf('payments_%s_%d.csv', md5($paymentsUpload->file->getFilename()), time());
                $this->get(SessionInterface::class)->set(CustomCsvImport::PAYMENTS_SESSION_KEY, $filename);
                $this->get(SessionInterface::class)->set(
                    CustomCsvImport::PAYMENTS_CTRL_SESSION_KEY,
                    $this->get(CsvImportHandler::class)->getCsvStructure($paymentsUpload)
                );
                $paymentsUpload->file->move($dir, $filename);

                if ($request->isXmlHttpRequest()) {
                    return $this->createAjaxRedirectResponse('import_payments_map');
                }

                return $this->redirectToRoute('import_payments_map');
            }
            if ($request->isXmlHttpRequest()) {
                $this->invalidateTemplate(
                    'import-payments-form-container',
                    'import_payments/components/form.html.twig',
                    [
                        'form' => $form->createView(),
                    ]
                );

                return $this->createAjaxResponse();
            }
        }

        return $this->render(
            'import_payments/index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/map", name="import_payments_map")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function mapPaymentsAction(Request $request): Response
    {
        $filename = $this->get(SessionInterface::class)->get(CustomCsvImport::PAYMENTS_SESSION_KEY);
        $ctrl = $this->get(SessionInterface::class)->get(CustomCsvImport::PAYMENTS_CTRL_SESSION_KEY);

        try {
            $path = $this->getImportDir() . $filename;
            $file = new File($path);
            if (! Files::checkEncoding($path)) {
                $this->addTranslatedFlash(
                    'warning',
                    'CSV file is not in valid UTF-8, special characters may not be imported correctly.'
                );
            }
            $import = $this->get(CustomCsvImportFactory::class)->create($file, $ctrl);
        } catch (FileNotFoundException $e) {
            $this->addTranslatedFlash('error', 'There is nothing to import.');

            return $this->redirectToRoute('import_payments_index');
        }

        $fields = $import->getFieldsForMap();
        $hash = $import->getFieldsHash();

        $mapping = $this->em->getRepository(CsvImportMapping::class)->findOneBy(
            [
                'hash' => $hash,
                'type' => CsvImportMapping::TYPE_PAYMENT,
            ]
        );
        if (! $mapping) {
            $mapping = new CsvImportMapping();
            $mapping->setHash($hash);
            $mapping->setType(CsvImportMapping::TYPE_PAYMENT);
        }

        $configPath = $this->getImportDir() . $filename . '.json';
        $config = @file_get_contents($configPath); // @ intentional
        if (false === $config) {
            if ($mapping->getId()) {
                $config = $mapping->getMapping();
            } else {
                $config = $import->guessFieldsMapPayment($fields);
            }
        } else {
            $config = Json::decode($config, Json::FORCE_ARRAY);
        }

        $form = $this->createForm(
            CsvImportPaymentType::class,
            $config,
            [
                'mapChoices' => $fields,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $config = $form->getData();
            foreach ($config as $key => $value) {
                if (! $form->has($key)) {
                    unset($config[$key]);
                }
            }

            $fs = new Filesystem();
            $fs->dumpFile($this->getImportDir() . $filename . '.json', Json::encode($config));

            $mappingFacade = $this->get(CsvImportMappingFacade::class);
            $mapping->setMapping($config);
            if ($mapping->getId()) {
                $mappingFacade->handleEdit($mapping);
            } else {
                $mappingFacade->handleNew($mapping);
            }

            return $this->redirectToRoute('import_payments_preview');
        }

        return $this->render(
            'import_payments/map.html.twig',
            [
                'form' => $form->createView(),
                'type' => 'payments',
            ]
        );
    }

    /**
     * @Route("/preview", name="import_payments_preview")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function previewPaymentsAction(Request $request): Response
    {
        try {
            list($payments, $errors, $errorSummary) = $this->getPayments(false);
        } catch (FileNotFoundException $e) {
            $this->addTranslatedFlash('error', 'There is nothing to import.');

            return $this->redirectToRoute('import_payments_index');
        }

        $import = $this->getImport(CustomCsvImport::PAYMENTS_SESSION_KEY);

        $populatedPayments = $import->getPopulatedPayments($payments, $this->getUser());
        $validPayments = [];
        $errorPayments = [];

        foreach ($payments as $key => $payment) {
            $errorCount = count($payment['_errors']);
            if (
                $errorCount > 0
                && (
                    ! array_key_exists('client', $payment['_errors'])
                    || $errorCount > 1
                )
            ) {
                $errorPayments[$key] = $populatedPayments[$key];
            } else {
                $validPayments[$key] = $populatedPayments[$key];
            }

            unset($populatedPayments[$key]);
        }

        $formName = 'csv_import_payments';
        $this->deserializeJsonForm($formName, $request, $validPayments);

        $checkboxes = [];
        foreach ($validPayments as $key => $payment) {
            $checkboxes[$key] = true;
        }

        $form = $this->get('form.factory')->createNamed(
            $formName,
            CsvImportPaymentsType::class,
            [
                'payments' => $validPayments,
                'importRow' => $checkboxes,
            ]
        );

        foreach ($errors as $key => $error) {
            foreach ($error['_errors'] as $input => $message) {
                if (! $form->get('payments')->has($key)) {
                    continue;
                }

                $form->get('payments')->get($key)->get($input)->addError(
                    new FormErrorDomain($message, null)
                );
                unset($error['_errors'][$input]);
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $importRows = $form->get('importRow')->getData();
            foreach ($importRows as $key => $enable) {
                if (! $enable) {
                    unset($validPayments[$key]);
                }
            }

            if ($validPayments) {
                // Clear EntityManager since we're moving to background and there are relations
                // on clients which we don't want (when $payment->setClient($client) is called)
                $this->em->clear();
                $count = $import->enqueuePaymentsImport($validPayments, $this->getUser());

                $this->addTranslatedFlash(
                    'success',
                    '%count% payments will be imported in the background within a few minutes.',
                    $count,
                    [
                        '%count%' => $count,
                    ]
                );

                $this->cleanup(CustomCsvImport::PAYMENTS_SESSION_KEY);

                if ($request->isXmlHttpRequest()) {
                    return $this->createAjaxRedirectResponse('payment_index');
                }

                return $this->redirectToRoute('payment_index');
            }

            $this->addTranslatedFlash('error', 'Nothing was imported.');

            if ($request->isXmlHttpRequest()) {
                return $this->createAjaxRedirectResponse('import_payments_index');
            }

            return $this->redirectToRoute('import_payments_index');
        }

        $parameters = [
            'validPayments' => $validPayments,
            'errorPayments' => $errorPayments,
            'csvPayments' => $this->getImport(CustomCsvImport::PAYMENTS_SESSION_KEY)->getRawCsvRows(),
            'errors' => $errors,
            'errorSummary' => $errorSummary,
            'form' => isset($form) ? $form->createView() : null,
        ];

        if ($request->isXmlHttpRequest()) {
            $this->invalidateTemplate(
                'payment-preview-container',
                'import_payments/components/preview_data.html.twig',
                $parameters
            );

            return $this->createAjaxResponse();
        }

        try {
            return $this->render(
                'import_payments/preview.html.twig',
                $parameters
            );
        } catch (\Twig_Error_Runtime $exception) {
            $this->addTranslatedFlash(
                'error',
                'CSV file is invalid and caused an error: %error%.',
                null,
                [
                    '%error%' => $exception->getMessage(),
                ]
            );

            return $this->redirectToRoute('import_payments_index');
        }
    }

    /**
     * @Route("/cancel", name="import_payments_cancel")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function cancelPaymentsAction(): Response
    {
        $this->cleanup(CustomCsvImport::PAYMENTS_SESSION_KEY);

        return $this->redirectToRoute('import_payments_index');
    }

    private function cleanup(string $key = CustomCsvImport::CLIENTS_SESSION_KEY): void
    {
        $filename = $this->get(SessionInterface::class)->get($key);

        if ($filename) {
            try {
                $fs = new Filesystem();
                $csv = $this->getImportDir() . $filename;
                $config = $this->getImportDir() . $filename . '.json';
                $fs->remove([$csv, $config]);
            } catch (IOException $e) {
                // we don't really care if it's deleted
            }
        }
    }

    private function getImport($key = CustomCsvImport::CLIENTS_SESSION_KEY): CustomCsvImport
    {
        $filename = $this->get(SessionInterface::class)->get($key);

        $file = new File($this->getImportDir() . $filename);
        $configPath = $this->getImportDir() . $filename . '.json';
        $config = @file_get_contents($configPath); // @ intentional
        if (false === $config) {
            throw new FileNotFoundException($configPath);
        }

        if ($key === CustomCsvImport::CLIENTS_SESSION_KEY) {
            $ctrl = $this->get(SessionInterface::class)->get(CustomCsvImport::CLIENTS_CTRL_SESSION_KEY);
        } else {
            $ctrl = $this->get(SessionInterface::class)->get(CustomCsvImport::PAYMENTS_CTRL_SESSION_KEY);
        }

        $import = $this->get(CustomCsvImportFactory::class)->create($file, $ctrl);
        $import->setFieldsMap(Json::decode($config, Json::FORCE_ARRAY));

        return $import;
    }

    private function getPayments(bool $skipInvalid = false): array
    {
        $payments = $this->getImport(CustomCsvImport::PAYMENTS_SESSION_KEY)->getPayments();

        $errors = [];
        $errorSummary = [];

        foreach ($payments as $key => $payment) {
            if ($payment['_errors'] ?? false) {
                if ($skipInvalid) {
                    unset($payments[$key]);
                } else {
                    $errors[$key] = $payment;
                }
            }

            if ($payment['_errorSummary'] ?? false) {
                foreach ($payment['_errorSummary'] as $error => $count) {
                    $errorSummary[$error] = ($errorSummary[$error] ?? 0) + $count;
                }
                unset($payments[$key]['_errorSummary']);
            }
        }

        return [$payments, $errors, $errorSummary];
    }

    private function getImportDir(): string
    {
        return $this->container->getParameter('kernel.root_dir') . CsvImporter::IMPORT_DIR;
    }

    private function deserializeJsonForm(string $formName, Request $request, array &$validPayments): void
    {
        // To exceed max_input_vars limit, we serialize each row into JSON.
        // The following code edits the request with unserialized info,
        // so that Symfony form can handle it and we can work normally.
        // @todo investigate - maybe this could be made as some extension to forms

        $formParameter = $request->request->get($formName);
        if (! $formParameter || ! array_key_exists('_jsonSerialized', $formParameter)) {
            return;
        }
        try {
            $formData = Json::decode($formParameter['_jsonSerialized']);
        } catch (JsonException $exception) {
            return;
        }

        $formParameter['importRow'] = [];
        $formParameter['payments'] = [];

        $handledPayments = [];

        foreach ($formData as $key => $value) {
            parse_str(sprintf('%s=%s', $key, $value), $result);

            if (array_key_exists('importRow', $result[$formName])) {
                $formParameter['importRow'][key($result[$formName]['importRow'])] = $value;
            }

            if (array_key_exists('payments', $result[$formName])) {
                $rowKey = key($result[$formName]['payments']);
                $fieldKey = key($result[$formName]['payments'][$rowKey]);

                $formParameter['payments'][$rowKey][$fieldKey] = $value;
                $handledPayments[] = $rowKey;
            }
        }

        foreach ($validPayments as $key => $payment) {
            if (! in_array($key, $handledPayments, true)) {
                unset($validPayments[$key]);
            }
        }

        $request->request->set($formName, $formParameter);
    }
}
