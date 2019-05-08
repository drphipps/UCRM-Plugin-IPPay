<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import;

use AppBundle\Facade\CsvImportFacade;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Handler\CsvImport\ClientCsvImportHandler;
use Doctrine\ORM\EntityManager;
use RabbitMqBundle\RabbitMqEnqueuer;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @deprecated Client CSV import was refactored, this class is obsolete and only used for Payment import, which is not yet refactored
 * @see https://ubnt.myjetbrains.com/youtrack/issue/UCRM-2807
 */
class CustomCsvImportFactory
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var ClientCsvImportHandler
     */
    private $clientCsvImportHandler;

    /**
     * @var CsvImportFacade
     */
    private $csvImportFacade;

    public function __construct(
        EntityManager $entityManager,
        ValidatorInterface $validator,
        PaymentFacade $paymentFacade,
        TranslatorInterface $translator,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        ClientCsvImportHandler $clientCsvImportHandler,
        CsvImportFacade $csvImportFacade
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->paymentFacade = $paymentFacade;
        $this->translator = $translator;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->clientCsvImportHandler = $clientCsvImportHandler;
        $this->csvImportFacade = $csvImportFacade;
    }

    public function create(File $file, array $ctrl): CustomCsvImport
    {
        return new CustomCsvImport(
            $file,
            $ctrl,
            $this->entityManager,
            $this->validator,
            $this->paymentFacade,
            $this->translator,
            $this->rabbitMqEnqueuer,
            $this->clientCsvImportHandler,
            $this->csvImportFacade
        );
    }
}
