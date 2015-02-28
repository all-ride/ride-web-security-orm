<?php

namespace ride\application\orm\model\behaviour;

use ride\application\orm\entry\OwnedEntry;

use \ride\library\orm\model\behaviour\AbstractBehaviour;
use \ride\library\orm\model\Model;

/**
 * Behaviour to keep the owner of a entry
 */
class OwnerBehaviour extends AbstractBehaviour {

    /**
     * Flag to see if the owner is a User entry instead of a username
     * @var boolean
     */
    protected $useUser;

    /**
     * Constructs a new behaviour
     * @param boolean $useUser Set to true to use the User entry instead of the
     * username
     */
    public function __construct($useUser = false) {
        $this->useUser = $useUser;
    }

    /**
     * Gets the owner for this behavious
     * @param \ride\library\orm\model\Model $model
     * @return string|\ride\library\security\model\User
     */
    protected function getOwner(Model $model) {
        if ($this->useUser) {
            return $model->getOrmManager()->getUser();
        }

        return $model->getOrmManager()->getUserName();
    }

    /**
     * Hook after creating a data container
     * @param \ride\library\orm\model\Model $model
     * @param mixed $entry
     * @return null
     */
    public function postCreateEntry(Model $model, $entry) {
        if (!$entry instanceof OwnedEntry || $entry->getOwner()) {
            return;
        }

        $owner = $this->getOwner($model);

        $entry->setOwner($owner);
    }

    /**
     * Hook before inserting an entry
     * @param \ride\library\orm\model\Model $model
     * @param mixed $entry
     * @return null
     */
    public function preInsert(Model $model, $entry) {
        if (!$entry instanceof OwnedEntry || $entry->getOwner()) {
            return;
        }

        $entry->setOwner($this->getOwner($model));
    }

}
