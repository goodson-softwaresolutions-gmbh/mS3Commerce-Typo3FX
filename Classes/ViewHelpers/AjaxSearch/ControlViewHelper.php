<?php
/***************************************************************
 * Part of mS3 Commerce Fx
 * Copyright (C) 2019 Goodson GmbH <http://www.goodson.at>
 *  All rights reserved
 *
 * Dieses Computerprogramm ist urheberrechtlich sowie durch internationale
 * Abkommen geschützt. Die unerlaubte Reproduktion oder Weitergabe dieses
 * Programms oder von Teilen dieses Programms kann eine zivil- oder
 * strafrechtliche Ahndung nach sich ziehen und wird gemäß der geltenden
 * Rechtsprechung mit größtmöglicher Härte verfolgt.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace Ms3\Ms3CommerceFx\ViewHelpers\AjaxSearch;


use Ms3\Ms3CommerceFx\Domain\Model\Attribute;
use Ms3\Ms3CommerceFx\Domain\Model\AttributeValue;
use Ms3\Ms3CommerceFx\Domain\Repository\AttributeRepository;
use Ms3\Ms3CommerceFx\Search\SearchContext;
use Ms3\Ms3CommerceFx\ViewHelpers\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class ControlViewHelper extends AbstractTagBasedViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('type', 'string', 'Type of control', true);
        $this->registerArgument('attribute', 'mixed', 'PIM Attribute this control filters', true);
        $this->registerArgument('variables', 'array', 'Additional ariables passed to control template', false);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $type = $arguments['type'];
        $ucType = ucfirst($type);
        $view = static::tryGetPartialView("Control/$ucType", $renderingContext, $arguments['variables']);
        if ($view === false) {
            $view = static::tryGetPartialView("Control/ControlBase", $renderingContext, $arguments['variables']);
        }

        $attrParam = $arguments['attribute'];
        if ($attrParam instanceof AttributeValue) {
            $attr = $attrParam->getAttribute();
        } else if ($attrParam instanceof Attribute) {
            $attr = $attrParam;
        } else if ($attrParam == null) {
            return '';
        } else {
            if (gettype($attrParam) == "object")
                throw new \Exception("Parameter 'attribute' must be AttributeValue or Attribute, " . get_class($attrParam) . " given");
            throw new \Exception("Parameter 'attribute' must be AttributeValue or Attribute, " . gettype($attrParam) . " given");
        }

        if ($view !== false) {
            $view->assign('attribute', $attr);
            $content = $view->render();
        } else {
            $content = '';
        }

        $class = 'mS3Control mS3'.$ucType;
        $arguments['class'] .= $class;

        $arguments['data'] = [
            'controltype' => $type,
            'attribute' => $attr->getName()
        ];

        SearchContext::currentContext()->registerFilterAttribute($arguments['attribute'], $type);

        return parent::renderTag('div', $content, $arguments);
    }
}
