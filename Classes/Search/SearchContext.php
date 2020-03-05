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
    private $registeredFilters = [];
    private $usedTablePostfixes = [];

    /** @var string */
    private $formId;
    /** @var string */
    private $resultElementId;

    var $isRestrictionFiltered = false;
    var $consolidatedOnLevel = false;
    var $isInitialized = false;
    var $handledMenuIds = [];
    var $handledFullTextMenuIds = [];
    var $filterAttributes = [];

    /** @var SearchContext */
    private static $currentContext = null;
    public static function currentContext() {
        if (self::$currentContext == null) {
            throw new \Exception('No search context');
        }
        return self::$currentContext;
    }

    public static function createContext() {
        if (self::$currentContext != null) {
            throw new \Exception('Already have a search context');
        }

        self::$currentContext = new SearchContext();
        return self::$currentContext;
    }

    public static function destroyContext() {
        self::$currentContext = null;
    }

    public function __construct()
    {
        $this->id = self::$_nextId;
        self::$_nextId++;
        $this->formId = 'mS3Form_' . $this->id;
        $this->resultElementId = 'mS3Result_' . $this->id;
    }

    public function getTableName($postfix = '') {
        $this->usedTablePostfixes[$postfix] = 1;
        return "mS3CSearch_$postfix{$this->id}";
    }

    public function getUsedTableNames() {
        return array_map([$this, 'getTableName'], array_keys($this->usedTablePostfixes));
    }

    public function isAttributeFiltered(): bool {
        return !empty($this->filterAttributes);
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

    /**
     * @return string
     */
    public function getResultElementId(): string
    {
        return $this->resultElementId;
    }

    /**
     * @param string $resultElementId
     */
    public function setResultElementId(string $resultElementId): void
    {
        $this->resultElementId = $resultElementId;
    }

    public function registerFilterAttribute($attributeName, $controlType) {
        $this->registeredFilters[] = ['attribute' => $attributeName, 'type' => $controlType];
    }

    public function getRegisteredFilters() {
        return $this->registeredFilters;
    }
}
