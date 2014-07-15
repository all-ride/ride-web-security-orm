<?php

namespace ride\web\security\model\orm;

use ride\library\orm\query\ModelQuery;
use ride\library\orm\model\GenericModel;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

use \Exception;

/**
 * User model
 */
class UserModel extends GenericModel {

    /**
     * Gets a user by the username
     * @param string $username The username of the user
     * @param UserData|null The user if found, null otherwise
     */
    public function getUserByUsername($username) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{username} = %1%', $username);

        $user = $query->queryFirst();
        if ($user) {
            $user->preparePermissions();
            $user->preparePaths();
        }

        return $user;
    }

    /**
     * Gets a user by email
     * @param string $email The email address of the user
     * @param UserData|null The user if found, null otherwise
     */
    public function getUserByEmail($email) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{email} = %1%', $email);

        $user = $query->queryFirst();

        if ($user) {
            $user->preparePermissions();
            $user->preparePaths();
        }

        return $user;
    }

    /**
     * Gets all the users with the provided permission
     * @param $permission
     * @return array Array with UserData objects
     */
    public function getUsersWithPermission($permission) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addJoin('INNER', PermissionModel::NAME . RoleModel::NAME, 'permissionRoles', '{permissionRoles.role} = {roles.id}');
        $query->addJoin('INNER', PermissionModel::NAME, 'permissions', '{permissionRoles.permission} = {permissions.id}');
        $query->addCondition('{roles.isSuperRole} = %1% OR {permissions.code} = %2%', true, $permission);
        $query->addOrderBy('{username} ASC');

        return $query->query();
    }

    /**
     * Validates a data object of this model
     * @param mixed $data Data object of the model
     * @return null
     * @throws ride\library\validation\exception\ValidationException when one of the fields is not valid
     */
    public function validate($data) {
        try {
            parent::validate($data);

            $exception = new ValidationException('Validation errors occured in ' . $this->getName());
        } catch (ValidationException $e) {
            $exception = $e;
        }

        if (isset($data->username) && !$exception->hasErrors('username')) {
            $query = $this->createQuery();
            $query->setRecursiveDepth(0);
            $query->addCondition('{username} = %1%', $data->username);
            if ($data->id) {
                $query->addCondition('{id} <> %1%', $data->id);
            }

            if ($query->count()) {
                $error = new ValidationError('orm.security.error.username.exists', 'Username %username% is already used by another user', array('username' => $data->username));
                $exception->addErrors('username', array($error));
            }
        }

        if (isset($data->email) && $data->email && !$exception->hasErrors('email')) {
            $query = $this->createQuery();
            $query->setRecursiveDepth(0);
            $query->addCondition('{email} = %1%', $data->email);
            if ($data->id) {
                $query->addCondition('{id} <> %1%', $data->id);
            }

            if ($query->count()) {
                $error = new ValidationError('orm.security.error.email.exists', 'Email address %email% is already used by another user', array('email' => $data->email));
                $exception->addErrors('email', array($error));
            }
        }

        if ($exception->hasErrors()) {
            throw $exception;
        }
    }

}
