<?php

namespace ride\application\orm\model\behaviour\initializer;

use ride\application\orm\model\behaviour\OwnerBehaviour;

use ride\library\generator\CodeClass;
use ride\library\generator\CodeGenerator;
use ride\library\orm\definition\field\BelongsToField;
use ride\library\orm\definition\field\PropertyField;
use ride\library\orm\definition\ModelTable;

/**
 * Setup the owner behaviour based on the model options
 */
class OwnerBehaviourInitializer implements BehaviourInitializer {

    /**
     * Gets the behaviours for the model of the provided model table
     * @param \ride\library\orm\definition\ModelTable $modelTable
     * @return array An array with instances of Behaviour
     * @see \ride\library\orm\model\behaviour\Behaviour
     */
    public function getBehavioursForModel(ModelTable $modelTable) {
        $useOwnerObject = $modelTable->getOption('behaviour.owner');
        if ($useOwnerObject === null) {
            return array();
        }

        $useOwnerObject = Boolean::getBoolean($useOwnerObject);

        if (!$modelTable->hasField('owner')) {
            if ($useOwnerObject) {
                $ownerField = new BelongsToField('owner', 'User');
            } else {
                $ownerField = new PropertyField('owner', 'string');
            }

            $ownerField->setOptions(array(
                'label' => 'label.owner',
                'scaffold.form.omit' => 'true',
            ));

            $modelTable->addField($ownerField);
        }

        return array(new OwnerBehaviour($useOwnerObject));
    }

    /**
     * Generates the needed code for the entry class of the provided model table
     * @param \ride\library\orm\definition\ModelTable $table
     * @param \ride\library\generator\CodeGenerator $generator
     * @param \ride\library\generator\CodeClass $class
     * @return null
     */
    public function generateEntryClass(ModelTable $modelTable, CodeGenerator $generator, CodeClass $class) {
        $useOwnerObject = $modelTable->getOption('behaviour.owner');
        if ($useOwnerObject === null) {
            return;
        }

        if ($useOwnerObject) {
            $class->addImplements('ride\\application\\orm\\entry\\OwnedEntry');
        } else {
            $class->addImplements('ride\\library\\orm\\entry\\OwnedEntry');
        }
    }

}
