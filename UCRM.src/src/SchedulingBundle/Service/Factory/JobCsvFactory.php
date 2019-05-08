<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Factory;

use AppBundle\Component\Csv\CsvBuilder;
use AppBundle\Util\DurationFormatter;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManager;
use SchedulingBundle\DataProvider\JobDataProvider;
use SchedulingBundle\Entity\Job;
use Symfony\Component\Translation\TranslatorInterface;

class JobCsvFactory
{
    /**
     * @var DurationFormatter
     */
    private $durationFormatter;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Formatter
     */
    private $formatter;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var JobDataProvider
     */
    private $jobDataProvider;

    public function __construct(
        DurationFormatter $durationFormatter,
        EntityManager $em,
        Formatter $formatter,
        TranslatorInterface $translator,
        JobDataProvider $jobDataProvider
    ) {
        $this->durationFormatter = $durationFormatter;
        $this->em = $em;
        $this->formatter = $formatter;
        $this->translator = $translator;
        $this->jobDataProvider = $jobDataProvider;
    }

    public function create(array $ids): string
    {
        $builder = new CsvBuilder();

        $jobs = $this->jobDataProvider->getAllByIds($ids);
        /** @var Job $job */
        foreach ($jobs as $job) {
            $data = [
                'Title' => $job->getTitle(),
                'Status' => $job->getStatus(),
                'Date' => $job->getDate() ? $this->formatter->formatDate($job->getDate(), Formatter::DEFAULT, Formatter::SHORT) : null,
                'Duration' => $job->getDuration() ? $this->durationFormatter->format($job->getDuration() * 60, DurationFormatter::SHORT) : null,
                'Assigned user' => $job->getAssignedUser() ? $job->getAssignedUser()->getNameForView() : null,
                'Client' => $job->getClient() ? $job->getClient()->getNameForView() : null,
            ];

            $builder->addData($data);
        }

        return $builder->getCsv();
    }
}
