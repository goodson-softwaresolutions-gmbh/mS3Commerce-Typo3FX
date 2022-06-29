<?php


namespace Ms3\Ms3CommerceFx\ViewHelpers;

class ImageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('srcOnly', 'bool', 'if true, only returns the URL, otherwise image tag', false, false);
        $this->registerArgument('placeholder', 'string', 'Replacement image if image doesn\'t exist', false);
        $this->registerArgument('placeholderTransform', 'bool', 'if true (default), parameters will also be applied to placeholder', false, true);
    }

    public function render()
    {
        $args = $this->arguments;
        $placeholder = $args['placeholder'];
        $trans = $args['placeholderTransform'];
        unset($args['placeholder']);
        unset($args['placeholderTransform']);
        $res = $this->doRender($args);

        if (!$res && $placeholder) {
            $args['src'] = $placeholder;
            unset($args['file']);
            if (!$trans) {
                unset($args['crop']);
                unset($args['cropVariant']);
                unset($args['fileExtension']);
                unset($args['width']);
                unset($args['height']);
                unset($args['minWidth']);
                unset($args['minHeight']);
                unset($args['maxWidth']);
                unset($args['maxHeight']);
            }
            $res = $this->doRender($args);
        }
        return $res;
    }

    private function doRender($args)
    {
        try {
            $this->arguments = $args;
            $res = parent::render();
            if ($this->arguments['srcOnly']) {
                return $this->tag->getAttribute('src');
            } else {
                return $res;
            }
        } catch (\Exception $e) {
            return '';
        }
    }
}
