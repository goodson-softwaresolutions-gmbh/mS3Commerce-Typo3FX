<?php

namespace Ms3\Ms3CommerceFx\ViewHelpers\Structure;

use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

class IsChildOfViewHelper extends AbstractConditionViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('child', 'mixed', 'The assumed child object', true);
        $this->registerArgument('parent', 'mixed', 'The assumed parent object', true);
        $this->registerArgument('direct', 'bool', 'Direct child or any descendent', false, true);
        $this->registerArgument('includeSelf', 'bool', 'Include the object itself', false, false);
    }

    protected static function evaluateCondition($arguments = null)
    {
        /** @var PimObject $c */
        $c = $arguments['child'];
        if (!$c instanceof PimObject) return false;
        /** @var PimObject $p */
        $p = $arguments['parent'];
        if (!$p instanceof PimObject) return false;

        if ($arguments['includeSelf']) {
            if ($p->getId() == $c->getId()) return true;
        }

        if ($arguments['direct']) {
            return $c->getParentObject()->getId() == $p->getId();
        }

        $chain = $c->getParentPath();
        foreach ($chain as $cc) {
            if ($cc->getId() == $p->getId()) return true;
        }

        return false;
    }
}
