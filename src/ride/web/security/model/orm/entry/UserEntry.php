<?php

namespace ride\web\security\model\orm\entry;

use ride\application\orm\entry\UserEntry as OrmUserEntry;
use ride\application\orm\entry\UserPreferenceEntry;

use ride\library\security\matcher\PathMatcher;
use ride\library\security\model\User;

/**
 * User data container
 */
class UserEntry extends OrmUserEntry implements User {

    /**
     * Flag to see if the password has been changed
     * @var boolean
     */
    private $isPasswordChanged;

    /**
     * Array with all the permissions of the roles
     * @var array
     */
    private $permissions;

    /**
     * Array with all the routes of the roles
     * @var array
     */
    private $paths;

    /**
     * Constructs a new user
     * @param integer $id
     * @param string $username
     * @param string $password
     * @return null
     */
    public function __construct($id = null, $username = null, $password = null) {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Sets the display name of this user
     * @param string $name
     * @return null
     */
    public function setDisplayName($name) {
        $this->name = $name;
    }

    /**
     * Gets the display name of this user
     * @return string
     */
    public function getDisplayName() {
        if (!$this->name) {
            return $this->username;
        }

        return $this->name;
    }

    /**
     * Sets a new password for this user
     *
     * This method will run the security.password.update event before setting the password. This event
     * has the User object and the new plain password as arguments.
     * @param string $password Plain text password
     * @return null
     * @see SecurityModel
     */
    public function setPassword($password) {
        parent::setPassword($password);

        $this->isPasswordChanged = true;
    }

    /**
     * Checks if the user password has been changed
     * @return boolean
     */
    public function isPasswordChanged() {
        return $this->isPasswordChanged;
    }

    /**
     * Clears the password changed flag
     * @return null
     */
    public function clearIsPasswordChanged() {
        $this->isPasswordChanged = null;
    }

    /**
     * Sets the email address of this user
     * @param string $email
     * @return
     */
    public function setEmail($email) {
        parent::setEmail($email);

        $this->setIsEmailConfirmed(false);
    }

    /**
     * Sets whether this user's email address has been confirmed
     * @param boolean $isConfirmed
     * @return null
     */
    public function setIsEmailConfirmed($isEmailConfirmed) {
        if (!$this->getEmail()) {
            parent::setIsEmailConfirmed(false);
        } else {
            parent::setIsEmailConfirmed($isEmailConfirmed);
        }
    }

    /**
     * Gets the highest weight of the user's roles
     * @return integer
     */
    public function getRoleWeight() {
        if ($this->isSuperUser()) {
            return 2147483647;
        }

        $roles = $this->getRoles();
        $weight = 0;

        foreach ($roles as $role) {
            $roleWeight = $role->getWeight();
            if ($roleWeight > $weight) {
                $weight = $roleWeight;
            }
        }

        return $weight;
    }

    /**
     * Checks whether a permission is granted for this user
     * @param string $code Code of the permission to check
     * @return boolean True if permission is granted, false otherwise
     * @see SecurityManager::ASTERIX
     */
    public function isPermissionGranted($code) {
        if (!isset($this->permissions)) {
            $this->preparePermissions();
        }

        if (isset($this->permissions[$code])) {
            return true;
        }

        return false;
    }

    /**
     * Prepares the permissions for a quicker permission check
     * @return null
     */
    public function preparePermissions() {
        $this->permissions = array();

        $roles = $this->getRoles();
        foreach ($roles as $role) {
            $rolePermissions = $role->getPermissions();
            foreach ($rolePermissions as $rolePermission) {
                $this->permissions[$rolePermission->getCode()] = true;
            }
        }
    }

    /**
     * Checks whether a path is allowed for this user
     * @param string $path Path to check
     * @param ride\library\security\matcher\PathMatcher $pathMatcher To match
     * path regular expression on the route
     * @return boolean True if the path is allowed, false otherwise
     */
    public function isPathAllowed($path, PathMatcher $pathMatcher) {
        if (!isset($this->paths)) {
            $this->preparePaths();
        }

        if ($pathMatcher->matchPath($path, $this->paths)) {
            return true;
        }

        return false;
    }

    /**
     * Prepares the paths for a quicker path check
     * @return null
     */
    public function preparePaths() {
        $this->paths = array();

        $roles = $this->getRoles();
        foreach ($roles as $role) {
            $rolePaths = $role->getRolePaths();
            foreach ($rolePaths as $rolePath) {
                $this->paths[$rolePath->getPath()] = true;
            }
        }

        $this->paths = array_keys($this->paths);
    }

    /**
     * Gets all the user preferences
     * @return array Array with the name of the preference as key and the
     * preference as value
     */
    public function getPreferences() {
        $preferences = array();

        $userPreferences = $this->getUserPreferences();
        foreach ($userPreferences as $userPreference) {
            $preferences[$userPreference->getName()] = unserialize($userPreference->getValue());
        }

        return $preferences;
    }

    /**
     * Gets a preference of this user
     * @param string $name Name of the preference
     * @param mixed $default Default value for when the preference is not set
     * @return mixed Value for the preference if set, the provided default
     * value otherwise
     */
    public function getPreference($name, $default = null) {
        $userPreferences = $this->getUserPreferences();
        foreach ($userPreferences as $userPreference) {
            if ($userPreference->getName() == $name) {
                return unserialize($userPreference->getValue());
            }
        }

        return $default;
    }

    /**
     * Sets a preference for this user
     * @param string $name Name of the preference
     * @param mixed $value Value of the preference
     * @return null
     */
    public function setPreference($name, $value) {
        $userPreferences = $this->getUserPreferences();
        foreach ($userPreferences as $userPreference) {
            if ($userPreference->getName() != $name) {
                continue;
            }

            $userPreference->setValue(serialize($value));

            return;
        }

        $preference = new UserPreferenceEntry();
        $preference->setName($name);
        $preference->setValue(serialize($value));

        $this->userPreferences[$name] = $preference;
    }

}
