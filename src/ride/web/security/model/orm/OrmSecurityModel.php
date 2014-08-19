<?php

namespace ride\web\security\model\orm;

use ride\library\encryption\hash\Hash;
use ride\library\event\Event;
use ride\library\event\EventManager;
use ride\library\orm\OrmManager;
use ride\library\security\model\ChainableSecurityModel;
use ride\library\security\model\Permission;
use ride\library\security\model\Role;
use ride\library\security\model\User;
use ride\library\security\SecurityManager;
use ride\library\system\System;

use ride\web\security\model\orm\entry\PermissionEntry;
use ride\web\security\model\orm\entry\RoleEntry;
use ride\web\security\model\orm\entry\UserEntry;

use \Exception;

/**
 * Orm implementation of the security model
 */
class OrmSecurityModel implements ChainableSecurityModel {

    /**
     * Instance of the model manager
     * @var ride\library\orm\OrmManager
     */
    private $orm;

    /**
     * Instance of the event manager
     * @var ride\library\event\EventManager
     */
    private $eventManager;

    /**
     * Instance of the hash algorithm
     * @var ride\library\encryption\hash\Hash
     */
    private $hashAlgorithm;

    /**
     * Constructs a new orm security model
     * @return null
     */
    public function __construct(OrmManager $orm, EventManager $eventManager, Hash $hashAlgorithm) {
        $this->orm = $orm;
        $this->eventManager = $eventManager;
        $this->hashAlgorithm = $hashAlgorithm;

        $this->eventManager->addEventListener(SecurityManager::EVENT_LOGIN, array($this, 'onLogin'));
    }

    /**
     * Checks if this model owns the provided user instance
     * @param User $user
     * @return boolean
     */
    public function ownsUser(User $user) {
        return $user instanceof UserEntry;
    }

    /**
     * Checks if this model owns the provided role instance
     * @param Role $role
     * @return boolean
     */
    public function ownsRole(Role $role) {
        return $role instanceof RoleEntry;
    }

    /**
     * Checks if this model owns the provided permission instance
     * @param Permission $permission
     * @return boolean
     */
    public function ownsPermission(Permission $permission) {
        return $permission instanceof PermissionEntry;
    }

    /**
     * Event listener to save the last access date and ip address of a user
     * @param ride\library\security\model\User $user User who is logging in
     * @return null
     */
    public function onLogin(Event $event, System $system) {
        $user = $event->getArgument('user');
        if (!$this->ownsUser($user)) {
            return;
        }

        $user->dateLastLogin = time();
        $user->lastIp = $system->getClient();

        $userModel = $this->orm->getUserModel();
        $userModel->save($user);
    }

    /**
     * Checks if the security model is ready to work
     * @return boolean True if the model is ready, false otherwise
     */
    public function ping() {
        try {
            $this->getSecuredPaths();

            return true;
        } catch (Exception $exception) {
            $log = $this->orm->getLog();
            if ($log) {
                $log->logException($exception);
            }

            return false;
        }
    }

    /**
     * Gets the secured paths
     * @return array Array with a path per element
     */
    public function getSecuredPaths() {
        $pathModel = $this->orm->getSecuredPathModel();

        return $pathModel->getSecuredPaths();
    }

    /**
     * Sets the secured paths
     * @param array $paths Array with a path per element
     * @return null
     */
    public function setSecuredPaths(array $paths) {
        $pathModel = $this->orm->getSecuredPathModel();
        $pathModel->setSecuredPaths($paths);
    }


    /**
     * Set the granted permissions to a Role
     * @param ride\library\security\model\Role $role Role to set the permissions to
     * @param array $permissions Array with a permission code per element
     * @return null
     */
    public function setGrantedPermissionsToRole(Role $role, array $permissions) {
        $modelPermissions = $this->getPermissions();

        $rolePermissions = array();

        foreach ($permissions as $code) {
            if (isset($modelPermissions[$code])) {
                $rolePermissions[$code] = $modelPermissions[$code];
            } else {
                $permission = new PermissionEntry();
                $permission->code = $code;
                $permission->description = $code;

                $rolePpermissions[$code] = $permission;
            }
        }

        $role->setPermissions($rolePermissions);

        $roleModel = $this->orm->getRoleModel();
        $roleModel->save($role);
    }

    /**
     * Set the allowed paths to a Role
     * @param ride\library\security\model\Role $role Role to set the routes to
     * @param array $paths Array with a path per element
     * @return null
     */
    public function setAllowedPathsToRole(Role $role, array $paths) {
        $roleModel = $this->orm->getRoleModel();
        $roleModel->setAllowedPathsToRole($role, $paths);
    }

    /**
     * Saves the provided roles for the provided user
     * @param ride\library\security\model\User $user
     * @param array $roles The roles to set to the user
     * @return null
     */
    public function setRolesToUser(User $user, array $roles) {
        $user->roles = $roles;

        $userModel = $this->orm->getUserModel();
        $userModel->save($user, 'roles');
    }

    /**
     * Gets a user by it's username
     * @param string $id Id of the user
     * @return \ride\library\security\model\orm\data\UserEntry|null User object
     * if found, null otherwise
     */
    public function getUserById($id) {
        $userModel = $this->orm->getUserModel();

        return $userModel->getById($id);
    }

    /**
     * Gets a user by it's username
     * @param string $username Username
     * @return \ride\library\security\model\orm\data\UserEntry|null User object
     * if found, null otherwise
     */
    public function getUserByUsername($username) {
        $userModel = $this->orm->getUserModel();

        return $userModel->getUserByUsername($username);
    }

    /**
     * Gets a user by it's email address
     * @param string $email Email address of the user
     * @return \ride\library\security\model\orm\data\UserEntry|null User object
     * if found, null otherwise
     */
    public function getUserByEmail($email) {
        $userModel = $this->orm->getUserModel();

        return $userModel->getUserByEmail($email);
    }

    /**
     * Gets the users
     * @param array $options Extra options for the query
     * <ul>
     *     <li>query</li>
     *     <li>name</li>
     *     <li>username</li>
     *     <li>email</li>
     *     <li>page</li>
     *     <li>limit</li>
     * </ul>
     * @return array
     */
    public function getUsers(array $options = null) {
        return $this->createUserQuery($options)->query();
    }

    /**
     * Counts the users
     * @param array $options Extra options for the query
     * <ul>
     *     <li>query</li>
     *     <li>name</li>
     *     <li>username</li>
     *     <li>email</li>
     * </ul>
     * @return integer
     */
    public function countUsers(array $options = null) {
        return $this->createUserQuery($options)->count();
    }

    /**
     * Creates the query to fetch and count the users
     * @param array $options Extra options for the query
     * @return \ride\library\orm\query\ModelQuery
     */
    protected function createUserQuery(array $options = null) {
        $userModel = $this->orm->getUserModel();

        $query = $userModel->createQuery();
        $query->setRecursiveDepth(1);
        $query->addOrderBy('{username}');

        if (!$options) {
            return $query;
        }

        if (isset($options['query'])) {
            $query->addCondition('{name} LIKE %1% OR {email} LIKE %1% OR {username} LIKE %1%', '%' . $options['query'] . '%');
        }
        if (isset($options['name'])) {
            $query->addCondition('{name} LIKE %1%', '%' . $options['name'] . '%');
        }
        if (isset($options['username'])) {
            $query->addCondition('{username} LIKE %1%', '%' . $options['username'] . '%');
        }
        if (isset($options['email'])) {
            $query->addCondition('{email} LIKE %1%', '%' . $options['email'] . '%');
        }

        if (isset($options['limit'])) {
            $page = isset($options['page']) ? $options['page'] : 1;
            $offset = ($page - 1) * $options['limit'];

            $query->setLimit($options['limit'], $offset);
        }

        return $query;
    }

    /**
     * Creates a new user
     * @return User
     */
    public function createUser() {
        $userModel = $this->orm->getUserModel();

        return $userModel->createEntry();
    }

    /**
     * Save a user
     * @param ride\library\security\model\User $user
     * @return null
     */
    public function saveUser(User $user) {
        if ($user->isPasswordChanged()) {
            $this->eventManager->triggerEvent(SecurityManager::EVENT_PASSWORD_UPDATE, array('user' => $user, 'password' => $user->password));

            if ($this->hashAlgorithm) {
                $user->setPassword($this->hashAlgorithm->hash($user->getPassword()));
                $user->clearIsPasswordChanged();
            }
        }

        $userModel = $this->orm->getUserModel();
        $userModel->save($user);
    }

    /**
     * Deletes the provided user
     * @param ride\library\security\model\User $user The user to delete
     * @return null
     */
    public function deleteUser(User $user) {
        $userModel = $this->orm->getUserModel();
        $userModel->delete($user);
    }

    /**
     * Gets a role by it's id
     * @param string $id Id of the role
     * @return Role|null Role object if found, null otherwise
     */
    public function getRoleById($id) {
        $roleModel = $this->orm->getRoleModel();

        return $roleModel->getById($id);
    }

    /**
     * Gets a role by it's name
     * @param string $name Name of the role
     * @return Role|null Role object if found, null otherwise
     */
    public function getRoleByName($name) {
        $roleModel = $this->orm->getRoleModel();

        return $roleModel->getRoleByName($name);
    }
    /**
     * Gets all the roles
     * @param array $options Options for the query
     * <ul>
     *     <li>query</li>
     *     <li>name</li>
     *     <li>page</li>
     *     <li>limit</li>
     * </ul>
     * @return array
     */
    public function getRoles(array $options = null) {
        return $this->createRoleQuery($options)->query();
    }

    /**
     * Counts the roles
     * @param array $options Extra options for the query
     * <ul>
     *     <li>query</li>
     *     <li>name</li>
     * </ul>
     * @return integer
     */
    public function countRoles(array $options = null) {
        return $this->createRoleQuery($options)->count();
    }

    /**
     * Creates the query to fetch and count the roles
     * @param array $options Extra options for the query
     * @return \ride\library\orm\query\ModelQuery
     */
    protected function createRoleQuery(array $options = null) {
        $roleModel = $this->orm->getRoleModel();

        $query = $roleModel->createQuery();
        $query->setRecursiveDepth(1);
        $query->addOrderBy('{name}');

        if (!$options) {
            return $query;
        }

        if (isset($options['query'])) {
            $query->addCondition('{name} LIKE %1%', '%' . $options['query'] . '%');
        }
        if (isset($options['name'])) {
            $query->addCondition('{name} LIKE %1%', '%' . $options['name'] . '%');
        }

        if (isset($options['limit'])) {
            $page = isset($options['page']) ? $options['page'] : 1;
            $offset = ($page - 1) * $options['limit'];

            $query->setLimit($options['limit'], $offset);
        }

        return $query;
    }

    /**
     * Creates a new role
     * @return ride\library\security\model\Role
     */
    public function createRole() {
        $roleModel = $this->orm->getRoleModel();

        return $roleModel->createEntry();
    }

    /**
     * Saves a role to the model
     * @param Role $role Role to save
     * @return null
     */
    public function saveRole(Role $role) {
        $roleModel = $this->orm->getRoleModel();
        $roleModel->save($role);
    }

    /**
     * Deletes a role from the model
     * @param ride\library\security\model\Role $role
     * @return null
     */
    public function deleteRole(Role $role) {
        $roleModel = $this->orm->getRoleModel();
        $roleModel->delete($role);
    }

    /**
     * Gets all the permissions
     * @return array Array with Permission objects
     */
    public function getPermissions() {
        $permissionModel = $this->orm->getPermissionModel();

        return $permissionModel->getPermissions();
    }

    /**
     * Check if the given permission exists in the model
     * @param string $code Code of the permission to check
     * @return boolean True if it exists, false otherwise
     */
    public function hasPermission($code) {
        $permissionModel = $this->orm->getPermissionModel();

        return $permissionModel->hasPermission($code);
    }

    /**
     * Adds a permission to the model if it does not exist
     * @param string $code Code of the permission to register
     * @return null
     */
    public function addPermission($code) {
        if ($this->hasPermission($code)) {
            return;
        }

        $permissionModel = $this->orm->getPermissionModel();

        $permission = $permissionModel->createEntry();
        $permission->code = $code;
        $permission->description = $code;

        $permissionModel->save($permission);
    }

    /**
     * Removes a permission from the model
     * @param string $code Code of the permission to remove
     * @return null
     */
    public function deletePermission($code) {
        if (!$this->hasPermission($code)) {
            return;
        }

        $permission = $this->permissions[$code];

        $permissionModel = $this->orm->getPermissionModel();
        $permissionModel->delete($permission);
    }

}
