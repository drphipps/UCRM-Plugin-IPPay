<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\SocketEvent;

class SocketEvent
{
    public const EVENT_NEW_HEADER_NOTIFICATION = 'header-notifications/new';

    /**
     * @var string
     */
    private $event;

    /**
     * @var int[]
     */
    private $userIds = [];

    /**
     * @var int|null
     */
    private $userGroupId;

    /**
     * @var string[]|null
     */
    private $roles;

    /**
     * Can be anything, that can be converted to JSON.
     *
     * @var mixed|null
     */
    private $data;

    public function __construct(string $event)
    {
        $this->event = $event;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return int[]
     */
    public function getUserIds(): array
    {
        return $this->userIds;
    }

    /**
     * @param int[] $userIds
     */
    public function setUserIds(array $userIds): void
    {
        $this->userIds = $userIds;
    }

    public function addUserId(int $userId): void
    {
        if (! in_array($userId, $this->userIds, true)) {
            $this->userIds[] = $userId;
        }
    }

    public function getUserGroupId(): ?int
    {
        return $this->userGroupId;
    }

    public function setUserGroupId(?int $userGroupId): void
    {
        $this->userGroupId = $userGroupId;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed|null $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    public function getBody(): array
    {
        if (! ($this->userIds || $this->userGroupId || $this->roles)) {
            throw new \InvalidArgumentException(
                'You must specify at least one of $userIds, $userGroupId or $roles.'
            );
        }

        return array_merge(
            array_filter(
                [
                    'userIds' => $this->userIds,
                    'userGroupId' => $this->userGroupId,
                    'roles' => $this->roles,
                ]
            ),
            [
                'data' => $this->data,
            ]
        );
    }
}
