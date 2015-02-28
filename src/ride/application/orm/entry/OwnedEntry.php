<?php

namespace ride\application\orm\entry;

use ride\library\orm\entry\OwnedEntry as LibOwnedEntry;

use ride\web\security\model\orm\entry\UserEntry as WebUserEntry;

/**
 * Interface for a owned entry
 */
interface OwnedEntry extends LibOwnedEntry {

    /**
     * Sets the owner of the entry
     * @param \ride\web\security\model\orm\entry\UserEntry $owner
     * @return null
     */
    public function setOwner(WebUserEntry $owner);

}
