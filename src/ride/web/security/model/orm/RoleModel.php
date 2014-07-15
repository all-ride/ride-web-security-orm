<?php

namespace ride\web\security\model\orm;

use ride\library\orm\model\GenericModel;

use ride\web\security\model\orm\entry\RoleEntry;

/**
 * Role model
 */
class RoleModel extends GenericModel {

    /**
     * Gets a role by it's name
     * @param string $name Name of the role
     * @return Role|null Role object if found, null otherwise
     */
    public function getRoleByName($name) {
        $query = $this->createQuery();
        $query->addCondition('{name} = %1%', $name);

        return $query->queryFirst();
    }

    /**
     * Finds roles by it's name
     * @param string $queryString Part of the name
     * @return array Array with Role objects
     */
    public function findRolesByName($queryString) {
        $query = $this->createQuery();
        $query->addCondition('{name} LIKE %1%', '%' . $queryString . '%');

        return $query->query();
    }

    /**
     * Sets the allowed paths for the provided role
     * @param ride\web\security\orm\entry\RoleEntry $role Role for the provided
     * paths
     * @param array $routes Array with a path string per element
     * @return null
     */
    public function setAllowedPathsToRole(RoleEntry $role, array $paths) {
        $pathModel = $this->orm->getSecuredPathModel();

        $role->setRolePaths($pathModel->getPathsFromArray($paths));

        $this->save($role);
    }

}
