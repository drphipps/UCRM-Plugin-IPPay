<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Exception\OptionNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Options
{
    public const EVENT_POST_REFRESH = 'ucrm.options.post_refresh';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Encryption
     */
    private $encryption;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var array
     */
    private $generalOptions = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        Encryption $encryption,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->encryption = $encryption;
        $this->dispatcher = $dispatcher;
    }

    public function get(string $code, $value = null)
    {
        if (empty($this->options)) {
            $this->load();
        }

        if (strtoupper($code) !== $code) {
            $code = strtoupper($code);
            @trigger_error('Passing non-uppercase option code is deprecated.', E_USER_DEPRECATED);
        }

        if (array_key_exists($code, $this->options)) {
            $value = $this->options[$code];

            if ($value && $code === Option::MAILER_PASSWORD) {
                $value = $this->encryption->decrypt($value);
            }
        }

        return $value;
    }

    public function getGeneral(string $code, $default = null)
    {
        if (empty($this->generalOptions)) {
            $this->loadGeneralOptions();
        }

        return array_key_exists($code, $this->generalOptions) ? $this->generalOptions[$code] : $default;
    }

    /**
     * Reload all options from DB.
     */
    public function refresh(): void
    {
        $this->load();
        $this->loadGeneralOptions();
        $this->dispatcher->dispatch(self::EVENT_POST_REFRESH);
    }

    public function reset(): void
    {
        $this->options = [];
        $this->generalOptions = [];
    }

    /**
     * Load options from DB.
     *
     * Intentionally not using EntityRepository::findAll() to avoid loading all the Option entities into memory.
     * Having them in memory would cause needless UnitOfWork::computeChangeSet() call for each of them on every flush.
     *
     * @throws OptionNotFoundException
     */
    private function load(): void
    {
        $this->options = $this->entityManager->getRepository(Option::class)->getAllOptions();

        if (! $this->options) {
            throw new OptionNotFoundException('No options exist in database.');
        }
    }

    /**
     * Load general options from DB.
     *
     * Intentionally not using EntityRepository::findAll() to avoid loading all the General entities into memory.
     * Having them in memory would cause needless UnitOfWork::computeChangeSet() call for each of them on every flush.
     */
    private function loadGeneralOptions(): void
    {
        $this->generalOptions = $this->entityManager->getRepository(General::class)->getAllOptions();
    }
}
