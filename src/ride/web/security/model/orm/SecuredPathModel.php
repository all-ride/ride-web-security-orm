<?php

namespace ride\web\security\model\orm;

use ride\library\orm\model\GenericModel;

/**
 * Secured path model
 */
class SecuredPathModel extends GenericModel {

    /**
     * Saves the secured paths to the model
     * @param array $paths Array with a path string per element
     * @return null
     * @throws Exception when an error occured
     */
    public function setSecuredPaths(array $paths) {
        $transactionStarted = $this->beginTransaction();

        try {
            // clear secured flag on all routes
            $query = $this->createQuery();
            $query->addCondition('{isSecured} = 1');

            $currentPaths = $query->query();
            foreach ($currentPaths as $path) {
                $path->setIsSecured(false);
            }

            $this->save($currentPaths);

            // set secured flag to provided routes
            $paths = $this->getPathsFromArray($paths);
            foreach ($paths as $path) {
                $path->setIsSecured(true);
            }

            $this->save($paths);

            $this->commitTransaction($transactionStarted);
        } catch (Exception $exception) {
            $this->rollbackTransaction($transactionStarted);
        }
    }

    /**
     * Gets the secured paths from the model
     * @return array Array with a path string as key and value
     */
    public function getSecuredPaths() {
        $query = $this->createQuery();
        $query->addCondition('{isSecured} = 1');

        $result = $query->query();

        $securedPaths = array();
        foreach ($result as $securedPath) {
            $securedPaths[$securedPath->getPath()] = $securedPath->getPath();
        }

        return $securedPaths;
    }

    /**
     * Get SecuredPathData objects from path strings
     * @param array $path Array with a path string per element
     * @return array Array with a SecuredPathData object per element
     */
    public function getPathsFromArray(array $paths) {
        $modelPaths = array();

        foreach ($paths as $path) {
            $query = $this->createQuery();
            $query->addCondition('{path} = %1%', $path);
            $modelPath = $query->queryFirst();

            if ($modelPath == null) {
                $modelPath = $this->createData(array(
                    'path' => $path,
                    'isSecured' => false,
                ));
            }

            $modelPaths[] = $modelPath;
        }

        return $modelPaths;
    }

}
