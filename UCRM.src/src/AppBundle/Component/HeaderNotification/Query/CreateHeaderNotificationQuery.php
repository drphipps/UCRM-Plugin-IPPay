<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\HeaderNotification\Query;

class CreateHeaderNotificationQuery implements QueryInterface
{
    /**
     * @var mixed[]
     */
    private $parameters;

    public function __construct(
        string $id,
        int $type,
        string $title,
        ?string $description,
        string $createdDate,
        ?string $link,
        bool $linkTargetBlank
    ) {
        $this->parameters = [
            'id' => $id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'createdDate' => $createdDate,
            'link' => $link,
            'linkTargetBlank' => $linkTargetBlank,
        ];
    }

    public function getQuery(): string
    {
        return '
          INSERT INTO
            header_notification (id, type, title, description, created_date, link, link_target_blank)
          VALUES
            (:id, :type, :title, :description, :createdDate, :link, :linkTargetBlank)
        ';
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameterTypes(): array
    {
        return [
            'linkTargetBlank' => \PDO::PARAM_BOOL,
        ];
    }
}
