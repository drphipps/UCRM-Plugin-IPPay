<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Outage;

use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Component\Ping\DeviceOutageProvider;
use AppBundle\Entity\Device;
use AppBundle\Util\DurationFormatter;
use AppBundle\Util\Formatter;

abstract class BaseOutageGridFactory
{
    /**
     * @var GridFactory
     */
    protected $gridFactory;

    /**
     * @var GridHelper
     */
    protected $gridHelper;

    /**
     * @var DeviceOutageProvider
     */
    protected $deviceOutageProvider;

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var DurationFormatter
     */
    protected $durationFormatter;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        DeviceOutageProvider $deviceOutageProvider,
        Formatter $formatter,
        DurationFormatter $durationFormatter,
        \Twig_Environment $twig
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->deviceOutageProvider = $deviceOutageProvider;
        $this->formatter = $formatter;
        $this->durationFormatter = $durationFormatter;
        $this->twig = $twig;
    }

    protected function renderDeviceStatusBall(int $status, string $label): string
    {
        switch ($status) {
            case Device::STATUS_ONLINE:
                $type = 'success';
                break;
            case Device::STATUS_UNREACHABLE:
                $type = 'warning';
                break;
            case Device::STATUS_DOWN:
                $type = 'danger';
                break;
            case Device::STATUS_UNKNOWN:
            default:
                $type = '';
                break;
        }

        return $this->twig->render(
            'client/components/view/status_ball.html.twig',
            [
                'type' => $type,
                'label' => $label,
            ]
        );
    }
}
