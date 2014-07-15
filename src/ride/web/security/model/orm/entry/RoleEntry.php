<?php

namespace ride\web\security\model\orm\entry;

use ride\application\orm\entry\RoleEntry as OrmRoleEntry;

use ride\library\security\model\Role;

/**
 * Role data container
 */
class RoleEntry extends OrmRoleEntry implements Role {

    /**
     * Allowed paths (string) for this role
     * @var array
     */
    private $paths = false;

    /**
     * Gets a string representation of this role
     * @return string
     */
    public function __toString() {
        if ($this->name) {
            return $this->name;
        }

        return parent::__toString();
    }

    /**
     * Gets the allowed paths of this role
     * @return array Array with a path regular expression per element
     */
    public function getPaths() {
        if ($this->paths !== false) {
            return $this->paths;
        }

        $this->paths = array();

        $rolePaths = $this->getRolePaths();
        if ($rolePaths) {
            foreach ($rolePaths as $path) {
                $this->paths[] = $path->getPath();
            }
        }

        return $this->paths;
    }

    /**
     * Checks whether a permission is granted for this role
     * @param string $code Code of the permission to check
     * @return boolean True if permission is granted, false otherwise
     */
    public function isPermissionGranted($code) {
        $permissions = $this->getPermissions();
        if (!$permissions) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($permission->getCode() == $code) {
                return true;
            }
        }

        return false;
    }

}
