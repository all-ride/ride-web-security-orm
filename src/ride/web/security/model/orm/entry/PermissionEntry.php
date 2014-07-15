<?php

namespace ride\web\security\model\orm\entry;

use ride\application\orm\entry\PermissionEntry as OrmPermissionEntry;

use ride\library\security\model\Permission;

/**
 * Permission data container
 */
class PermissionEntry extends OrmPermissionEntry implements Permission {

    /**
     * Gets a string representation of this permission
     * @return string
     */
    public function __toString() {
        if ($this->code) {
            return $this->code;
        }

        return parent::__toString();
    }

    /**
     * Gets the description of this permission
     * @return string
     */
    public function getDescription() {
        if (empty($this->description)) {
            return $this->code;
        }
        return $this->description;
    }

    // /**
    //  * Creates a new permission by the state of the properties
    //  * @param array $properties
    //  * @return PermissionData
    //  * @see var_export
    //  */
    // public static function __set_state($properties) {
    //     $permission = new self;

    //     foreach ($properties as $key => $value) {
    //         $permission->$key = $value;
    //     }

    //     return $permission;
    // }

}
