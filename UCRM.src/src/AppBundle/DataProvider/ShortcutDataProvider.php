<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Shortcut;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

class ShortcutDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    /**
     * @return Shortcut[]
     */
    public function get(User $user): array
    {
        $shortcuts = $this->entityManager->getRepository(Shortcut::class)->findBy(
            [
                'user' => $user,
            ],
            [
                'sequence' => 'ASC',
            ]
        );

        foreach ($shortcuts as $shortcut) {
            try {
                $shortcut->setUrl(
                    $this->router->generate(
                        $shortcut->getRoute(),
                        $shortcut->getParameters()
                    ) . ($shortcut->getSuffix() ?? '')
                );
            } catch (\Exception $exception) {
                $shortcut->setUrl(null);
            }
        }

        return $shortcuts;
    }
}
