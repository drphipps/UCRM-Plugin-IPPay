<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\NotificationTemplate;
use Doctrine\ORM\EntityManagerInterface;

class NotificationTemplateFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleUpdateTemplates(array $templates): void
    {
        $this->entityManager->transactional(
            function () use ($templates) {
                foreach ($templates as $template) {
                    if (is_array($template)) {
                        foreach ($template as $item) {
                            $this->handleUpdateTemplate($item);
                        }
                    } else {
                        $this->handleUpdateTemplate($template);
                    }
                }
            }
        );
    }

    private function handleUpdateTemplate(NotificationTemplate $template): void
    {
        $body = $template->getBody();
        if ($body && $body === strip_tags($body)) {
            $body = nl2br($body);
        }
        $template->setBody($body);
    }
}
