<?php


namespace Ms3\Ms3CommerceFx\ViewHelpers;

class ImageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('srcOnly', 'bool', 'if true, only returns the URL, otherwise image tag', false, false);
    }

    public function render()
    {
        try {
            $res = parent::render();
            if ($this->arguments['srcOnly']) {
                return $this->tag->getAttribute('src');
            } else {
                return $res;
            }
        } catch (\Exception $e) {
            return "";
        }
    }
}
