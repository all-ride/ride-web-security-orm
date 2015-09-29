<?php

namespace ride\web\security\model\orm;

use ride\library\orm\model\GenericModel;

/**
 * Permission model
 */
class PermissionModel extends GenericModel {

    /**
     * Gets all the permissions
     * @return array Array with PermissionData objects as value and the code as key
     */
    public function getPermissions() {
        $query = $this->createQuery();
        $query->addOrderBy('{code} ASC');

        return $query->query('code');
    }

    /**
     * Gets a permission from the model
     * @param string $code Code of the permission
     * @return \ride\library\security\model\Permission|null
     */
    public function getPermission($code) {
        $query = $this->createQuery();
        $query->addCondition('{code} = %1%', $code);

        return $query->queryFirst();
    }

    /**
     * Check if the given permission exists in the model
     * @param string $code Code of the permission to check
     * @return boolean True if it exists, false otherwise
     */
    public function hasPermission($code) {
        $query = $this->createQuery();
        $query->addCondition('{code} = %1%', $code);

        if ($query->count()) {
            return true;
        }

        return false;
    }

}
