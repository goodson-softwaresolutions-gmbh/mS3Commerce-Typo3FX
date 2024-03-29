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

namespace Ms3\Ms3CommerceFx\Domain\Model;

class ShopInfo extends AbstractEntity
{
    protected $shopId;
    protected $languageId;
    protected $marketId;
    protected $startId;
    protected $endId;
    protected $rootGroupId;
    protected $baseExportDate;
    protected $importDate;
    protected $uploadDate;

    public function __construct(int $id)
    {
        parent::__construct($id);
    }

    /**
     * @return int
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @return int
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * @return int
     */
    public function getMarketId()
    {
        return $this->marketId;
    }

    /**
     * @return int
     */
    public function getStartId()
    {
        return $this->startId;
    }

    /**
     * @return int
     */
    public function getEndId()
    {
        return $this->endId;
    }

    /**
     * @return int
     */
    public function getRootGroupId()
    {
        return $this->rootGroupId;
    }

    /**
     * @return string
     */
    public function getBaseExportDate()
    {
        return $this->baseExportDate;
    }

    /**
     * @return string
     */
    public function getImportDate()
    {
        return $this->importDate;
    }

    /**
     * @return string
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function containsId(int $id): bool
    {
        return $this->startId <= $id
            && $id < $this->endId;
    }
}
