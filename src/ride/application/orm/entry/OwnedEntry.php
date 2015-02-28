<?php

namespace ride\application\orm\entry;

use ride\web\security\model\orm\entry\UserEntry as WebUserEntry;

/**
 * Interface for a owned entry
 */
interface OwnedEntry {

    /**
     * Sets the owner of the entry
     * @param \ride\web\security\model\orm\entry\UserEntry $owner
     * @return null
     */
    public function setOwner(WebUserEntry $owner);

    /**
     * Gets the owner of the entry
     * @return \ride\web\security\model\orm\entry\UserEntry|null
     */
    public function getOwner();

}
