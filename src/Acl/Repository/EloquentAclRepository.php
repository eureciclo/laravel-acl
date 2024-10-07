<?php

namespace Signes\Acl\Repository;

use Exception;
use Signes\Acl\Contract\AclRepository;
use Signes\Acl\Contract\GroupInterface;
use Signes\Acl\Contract\HavingPermissionsInterface;
use Signes\Acl\Contract\HavingRolesInterface;
use Signes\Acl\Contract\PermissionInterface;
use Signes\Acl\Contract\RoleInterface;
use Signes\Acl\Contract\UserInterface;
use Signes\Acl\Exception\DuplicateEntryException;

/**
 * Class EloquentAclRepository
 *
 * @package Signes\Acl\Repository
 */
class EloquentAclRepository implements AclRepository
{
    /**
     * Application namespace
     *
     * @var string
     */
    protected $appNamespace = "App";

    /**
     * Set different site namespace
     *
     * @param string $namespace , new namespace
     */
    public function setSiteNamespace($namespace)
    {
        $this->appNamespace = (string) $namespace;
    }

    /**
     * Return Guest object.
     * Guest object is taken when no user is set in acl system.
     *
     * @return UserInterface
     */
    public function getGuest()
    {
        return $this->getModelName('User')->find(1);
    }

    /**
     * Create and persist new permission.
     *
     * @param string $area        ,area name
     * @param string $permission  , permission name
     * @param array|null $actions , permission actions
     * @param string $description , permission description
     * @return false|PermissionInterface
     */
    public function createPermission($area, $permission, $actions = null, $description = null)
    {
        // Cast data
        $actions = (array) $actions;

        // Check if permissions not exists yet
        if ($this->getModelName('Permission')
            ->where('area', '=', $area)
            ->where('permission', '=', $permission)
            ->first()
        ) {
            return false;
        }

        // Create new object
        $newPermission = $this->getModelName('Permission');
        $newPermission->setArea($area)
            ->setPermission($permission)
            ->setActions($actions)
            ->setDescription($description)
            ->save();

        return $newPermission;
    }

    /**
     * Delete permission from base.
     * We can remove whole zone, zone with permission, or one specific action.
     *
     * @param string $area               , are name
     * @param string|null $permission    , permission name
     * @param array|string|null $actions , action(s) name
     * @return false|int , number of removed items or false is error occurred
     */
    public function deletePermission($area, $permission = null, $actions = null)
    {
        // Cast data
        $area = (string) $area;

        /**
         * Delete area
         */
        if ($permission === null && $actions === null) {
            return $this->getModelName('Permission')->where('area', '=', $area)->delete();
        }

        /**
         * Delete area and zone
         */
        if (is_string($permission) && $actions === null) {
            return $this->getModelName('Permission')
                ->where('area', '=', $area)
                ->where('permission', '=', $permission)
                ->delete();
        }

        /**
         * Keep row in database, but remove actions from array
         */
        if (is_string($permission) && $actions !== null) {
            $actions = !is_array($actions) ? [(string) $actions] : $actions;
            $permission = $this->getModelName('Permission')->where('area', '=', $area)->where(
                'permission',
                '=',
                $permission
            )->first();

            if ($permission) {
                $currentActions = $permission->getActions();
                foreach ($actions as $action) {
                    if (($key = array_search($action, $currentActions)) !== false) {
                        unset($currentActions[$key]);
                    }
                }

                return $permission->setActions($currentActions)->save();
            }
        }

        return false;
    }

    /**
     *  Grant new permissions for user
     *
     * @param PermissionInterface $permission
     * @param UserInterface $user
     * @param array|true $actions , actions array or true, if true all actions will be granted
     * @throws DuplicateEntryException
     */
    public function grantUserPermission(PermissionInterface $permission, UserInterface $user, $actions = [])
    {
        $this->grantEntityPermission($permission, $user, $actions);
    }

    /**
     *  Grant new permissions for group
     *
     * @param PermissionInterface $permission
     * @param GroupInterface $group
     * @param array $actions , actions array or true, if true all actions will be granted
     * @throws DuplicateEntryException
     */
    public function grantGroupPermission(PermissionInterface $permission, GroupInterface $group, $actions = [])
    {
        $this->grantEntityPermission($permission, $group, $actions);
    }

    /**
     *  Grant new permissions for group
     *
     * @param PermissionInterface $permission
     * @param RoleInterface $role
     * @param array $actions , actions array or true, if true all actions will be granted
     * @throws DuplicateEntryException
     */
    public function grantRolePermission(PermissionInterface $permission, RoleInterface $role, $actions = [])
    {
        $this->grantEntityPermission($permission, $role, $actions);
    }

    /**
     *  Grant new role for user
     *
     * @param RoleInterface $role
     * @param UserInterface $user
     * @throws DuplicateEntryException
     */
    public function grantUserRole(RoleInterface $role, UserInterface $user)
    {
        try {
            $user->getRoles()->attach($role->getAttribute('id'));
        } catch (Exception $e) {
            throw new DuplicateEntryException($e->getMessage());
        }
    }

    /**
     * Grant new role for group
     *
     * @param RoleInterface $role
     * @param GroupInterface $group
     * @throws DuplicateEntryException
     */
    public function grantGroupRole(RoleInterface $role, GroupInterface $group)
    {
        try {
            $group->getRoles()->attach($role->getAttribute('id'));
        } catch (Exception $e) {
            throw new DuplicateEntryException($e->getMessage());
        }
    }

    /**
     * Revoke user permission
     *
     * @param PermissionInterface $permission
     * @param UserInterface $user
     * @return bool
     */
    public function revokeUserPermission(PermissionInterface $permission, UserInterface $user)
    {
        return (bool) $user->getPermissions()->detach($permission->getAttribute('id'));
    }

    /**
     * Revoke group permissions
     *
     * @param PermissionInterface $permission
     * @param GroupInterface $group
     * @return bool
     */
    public function revokeGroupPermission(PermissionInterface $permission, GroupInterface $group)
    {
        return (bool) $group->getPermissions()->detach($permission->getAttribute('id'));
    }

    /**
     * Revoke role permissions
     *
     * @param PermissionInterface $permission
     * @param RoleInterface $role
     * @return bool
     */
    public function revokeRolePermission(PermissionInterface $permission, RoleInterface $role)
    {
        return (bool) $role->getPermissions()->detach($permission->getAttribute('id'));
    }

    /**
     * Revoke User Role
     *
     * @param RoleInterface $role
     * @param UserInterface $user
     * @return bool
     */
    public function revokeUserRole(RoleInterface $role, UserInterface $user)
    {
        return (bool) $user->getRoles()->detach($role->getAttribute('id'));
    }

    /**
     * Revoke Group Role
     *
     * @param RoleInterface $role
     * @param GroupInterface $group
     * @return bool
     */
    public function revokeGroupRole(RoleInterface $role, GroupInterface $group)
    {
        return (bool) $group->getRoles()->detach($role->getAttribute('id'));
    }

    /**
     * Get model with correct site namespace
     *
     * @param $model
     * @return mixed
     */
    protected function getModelName($model)
    {
        $model = "{$this->appNamespace}\\Models\\Acl\\{$model}";
        return new $model;
    }

    /**
     * Grant entity permission
     *
     * @param PermissionInterface $permission
     * @param $entity        , one of object which implement 'getPermissions' method
     * @param array $actions , actions array or true, if true all actions will be granted
     * @throws DuplicateEntryException
     */
    private function grantEntityPermission(
        PermissionInterface $permission,
        HavingPermissionsInterface $entity,
        $actions = []
    ) {
        try {
            $actions = ($actions === true) ? $permission->getActions() : $actions;
            $entity->getPermissions()->attach($permission->getAttribute('id'), [
                'actions' => serialize($actions),
            ]);
        } catch (Exception $e) {
            throw new DuplicateEntryException($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     * @param HavingPermissionsInterface $object
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPermissionsFor(HavingPermissionsInterface $object)
    {
        return $object->getPermissions;
    }

    /**
     * {@inheritdoc}
     * @param HavingRolesInterface $object
     */
    public function getRolesFor(HavingRolesInterface $object)
    {
        return $object->getRoles;
    }
}
