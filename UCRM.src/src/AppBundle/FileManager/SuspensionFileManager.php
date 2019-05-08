<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Symfony\Component\Filesystem\Filesystem;

class SuspensionFileManager
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $suspensionFilePath;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var Options
     */
    private $options;

    public function __construct(string $suspensionFilePath, \Twig_Environment $twig, Options $options)
    {
        $this->filesystem = new Filesystem();
        $this->suspensionFilePath = $suspensionFilePath;
        $this->twig = $twig;
        $this->options = $options;
    }

    public function regenerateSuspensionFile(): void
    {
        $serverIp = $this->options->get(Option::SERVER_IP);
        $serverSuspendPort = $this->options->get(Option::SERVER_SUSPEND_PORT);

        if (! $serverIp) {
            $this->filesystem->remove($this->suspensionFilePath);
        } else {
            $content = $this->twig->render(
                'suspend/suspension.html.twig',
                [
                    'serverIp' => $serverIp,
                    'serverSuspendPort' => $serverSuspendPort,
                ]
            );

            $this->filesystem->dumpFile($this->suspensionFilePath, $content);
        }
    }
}
