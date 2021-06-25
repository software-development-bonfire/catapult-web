<?php

namespace App\Traits;

use App\Enums\Permissions;

trait HasPermission
{
    public function hasPermissionTo($permissions)
    {
        $userPermissions = app()->get('request')->session()->get('permissions');

        $permissions = explode('|', $permissions);

        if (! is_array($permissions)) {
            $permissions = [$permissions];
        }

        $isSuperadmin = auth()->user()->isSuperadmin();

        if (
            ($isSuperadmin && in_array('superadmin', $permissions))
            || (! in_array('superadmin', $permissions)
                && in_array(Permissions::LIST['general.administrator'], $userPermissions))
            || (! in_array('superadmin', $permissions)
                && in_array('master', $permissions))
        ) {
            return true;
        }

        return $this->isPermissible($permissions, $userPermissions, count($permissions) > 1 ? 'or' : null);
    }

    private function getPermissionCode($permission)
    {
        $permissionList = $this->getPermissionList();

        if (array_key_exists($permission, $permissionList)) {
            return $permissionList[$permission];
        }

        return null;
    }

    private function getPermissionList()
    {
        return array_dot(Permissions::LIST);
    }

    private function isPermissible($permissions, $userPermissions, $operator)
    {
        $permissible = false;

        foreach ($permissions as $permission) {
            if (strpos($permission, '&') !== false) {
                $permissible = $this->isPermissible(explode('&', $permission), $userPermissions, 'and');
            } else {
                $code = $this->getPermissionCode($permission);

                $permissible = in_array($code, $userPermissions);

                if (
                    count($permissions) > 1
                    && ($permissible && $operator == 'or')
                        || (! $permissible && $operator == 'and')
                ) {
                    break;
                }
            }
        }

        return $permissible;
    }
}
