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

namespace Ms3\Ms3CommerceFx\Search;


class SearchContext
{
    private static $_nextId = 0;
    private $id;
    /** @var string */
    private $formId;
    private $registeredFilters = [];
    private $searchMenuIds = [];

    /** @var SearchContext */
    static $currentContext = null;
    public static function currentContext() {
        if (self::$currentContext == null) {
            throw new \Exception('No form context');
        }
        return self::$currentContext;
    }

    public static function createContext() {
        if (self::$currentContext != null) {
            throw new \Exception('Already have a context');
        }

        self::$currentContext = new SearchContext();
        return self::$currentContext;
    }

    public static function destroyContext() {
        self::$currentContext = null;
    }

    private function __construct()
    {
        $this->id = self::$_nextId;
        self::$_nextId++;
        $this->formId = 'mS3Form_' . $this->id;
    }

    /**
     * @return string
     */
    public function getFormId(): string
    {
        return $this->formId;
    }

    /**
     * @param string $formId
     */
    public function setFormId(string $formId): void
    {
        $this->formId = $formId;
    }

    public function registerFilterAttribute($attributeName, $controlType)
    {
        $this->registeredFilters[] = ['attribute' => $attributeName, 'type' => $controlType];
    }

    public function registerSearchMenuId($menuIds) {
        $this->searchMenuIds = $menuIds;
    }
}