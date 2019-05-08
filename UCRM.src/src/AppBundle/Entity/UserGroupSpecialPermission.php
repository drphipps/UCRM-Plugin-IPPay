<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Security\SpecialPermission;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserGroupSpecialPermissionRepository")
 */
class UserGroupSpecialPermission implements ParentLoggableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="special_permission_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $groupSpecialPermissionId;

    /**
     * @var string
     *
     * @ORM\Column(name="module_name", type="string", length=255)
     * @Assert\Length(max = 255)
     */
    private $moduleName;

    /**
     * @var string
     *
     * @ORM\Column(name="permission", type="string", length=20, options={"default":"deny"})
     * @Assert\Length(max = 20)
     * @Assert\NotBlank()
     */
    private $permission = SpecialPermission::DENIED;

    /**
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="specialPermissions")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="group_id", onDelete = "CASCADE")
     */
    protected $group;

    /**
     * Get groupPermissionId.
     *
     * @return int
     */
    public function getGroupSpecialPermissionId()
    {
        return $this->groupSpecialPermissionId;
    }

    /**
     * Set moduleName.
     *
     * @param string $moduleName
     *
     * @return UserGroupSpecialPermission
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    /**
     * Get moduleName.
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Set permission.
     *
     * @param string $permission
     *
     * @return UserGroupSpecialPermission
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Get permission.
     *
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Set group.
     *
     * @param UserGroup $group
     *
     * @return UserGroupSpecialPermission
     */
    public function setGroup(UserGroup $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return UserGroup
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return bool
     */
    public function isSpecialPermissionSet(string $moduleName, string $permission)
    {
        return null !== $this->moduleName &&
            null !== $this->permission &&
            $this->moduleName == $moduleName &&
            $this->permission == $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getGroupSpecialPermissionId(),
            'message' => $this->getPermission(),
            'entity' => self::class,
        ];

        return $message;
    }
}
