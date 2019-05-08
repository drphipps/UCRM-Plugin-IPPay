<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\QoS;

use Nette\Utils\Strings;
use Symfony\Component\Filesystem\Filesystem;

class CommandLogger
{
    const LOG_PATH = '/qos';

    const TYPE_COMMAND = 'COMMAND';
    const TYPE_OUTPUT = 'OUTPUT';

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $logDir;

    /**
     * @var resource|null
     */
    private $stream;

    public function __construct(string $environment, string $logDir)
    {
        $this->environment = $environment;
        $this->logDir = $logDir;
    }

    public function logCommand(string $command, array $info = [])
    {
        $this->write(
            $this->buildContent($command, self::TYPE_COMMAND, $info)
        );
    }

    public function logOutput(string $output, array $info = [])
    {
        $this->write(
            $this->buildContent($output, self::TYPE_OUTPUT, $info)
        );
    }

    private function write(string $content)
    {
        $this->open();

        fwrite($this->stream, $content);

        // For some reason mocking class with destructor causes segfault error.
        // So file must be closed after each write.
        $this->close();
    }

    private function open()
    {
        if (null === $this->stream) {
            $fs = new Filesystem();
            if (! $fs->exists($this->getFileDir())) {
                $fs->mkdir($this->getFileDir());
            }

            $this->stream = fopen($this->getFilePath(), 'a+');
        }
    }

    private function close()
    {
        if (null !== $this->stream) {
            fclose($this->stream);

            $this->stream = null;
        }
    }

    private function getFilePath(): string
    {
        return sprintf(
            '%s/%s%d.log',
            $this->getFileDir(),
            $this->environment,
            (new \DateTime())->format('Ym')
        );
    }

    private function getFileDir(): string
    {
        return sprintf(
            '%s%s',
            $this->logDir,
            self::LOG_PATH
        );
    }

    private function buildContent(string $content, string $type, array $info = []): string
    {
        $header = sprintf(
            '[%s] %s %s',
            (new \DateTime())->format('Y-m-d H:i:s'),
            $type,
            $this->buildInfo($info)
        );

        $content = sprintf(
            '%s%s%s%s%s',
            Strings::trim($header),
            PHP_EOL,
            Strings::trim($content),
            PHP_EOL,
            PHP_EOL
        );

        return $content;
    }

    private function buildInfo(array $infoArray): string
    {
        $info = [];

        if (array_key_exists('ip', $infoArray)) {
            $info[] = $infoArray['ip'];
        }

        if (array_key_exists('os', $infoArray)) {
            $info[] = $infoArray['os'];
        }

        if (array_key_exists('device', $infoArray)) {
            $info[] = $infoArray['device'];
        }

        return implode(' ', array_filter($info));
    }
}
