<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\DeviceInterface;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Controller\DeviceInterfaceController;
use AppBundle\DataProvider\DeviceInterfaceDataProvider;
use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterface;
use Nette\Utils\Html;
use Nette\Utils\Strings;

class DeviceInterfaceGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var DeviceInterfaceDataProvider
     */
    private $deviceInterfaceDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        DeviceInterfaceDataProvider $deviceInterfaceDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->deviceInterfaceDataProvider = $deviceInterfaceDataProvider;
    }

    public function create(Device $device): Grid
    {
        $qb = $this->deviceInterfaceDataProvider->getGridModel($device);
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);

        $grid->setDefaultSort('di_name');
        $grid->addIdentifier('di_id', 'di.id');
        $grid->addRouterUrlParam('id', $device->getId());
        $grid->setRowUrl('interface_show');

        $grid->attached();

        $grid->addTextColumn('di_name', 'di.name', 'Name')
            ->setSortable();
        $grid->addTextColumn('di_type', 'di.type', 'Type')
            ->setReplacements(DeviceInterface::TYPES)
            ->setSortable();

        $grid
            ->addCustomColumn(
                'ipAddresses',
                'IP addresses',
                function ($row) {
                    if (! $row['ipAddresses']) {
                        return BaseColumn::EMPTY_COLUMN;
                    }

                    $ips = explode(',', $row['ipAddresses']);
                    $ips = array_map(
                        function ($ip) {
                            $ip = explode('/', $ip);
                            $ip = array_filter($ip, function (string $value) {
                                return $value || $value === '0';
                            });
                            $ip[0] = long2ip((int) $ip[0]);

                            return implode('/', $ip);
                        },
                        $ips
                    );

                    return implode(', ', $ips);
                }
            )
            ->setSortable();

        $grid->addRawCustomColumn(
            'notes',
            'Notes',
            function ($row) {
                /** @var DeviceInterface $deviceInterface */
                $deviceInterface = $row[0];
                $notes = $deviceInterface->getNotes();

                if (empty($notes)) {
                    return BaseColumn::EMPTY_COLUMN;
                }

                if (Strings::length($notes) > 80) {
                    $span = Html::el(
                        'span',
                        [
                            'data-tooltip' => $notes,
                        ]
                    );
                    $span->setText(Strings::truncate($deviceInterface->getNotes(), 80));

                    return (string) $span;
                }

                return htmlspecialchars($notes ?? '', ENT_QUOTES);
            }
        );

        $grid->addEditActionButton('interface_edit', [], DeviceInterfaceController::class);
        $grid->addDeleteActionButton('interface_delete', [], DeviceInterfaceController::class);

        return $grid;
    }
}
