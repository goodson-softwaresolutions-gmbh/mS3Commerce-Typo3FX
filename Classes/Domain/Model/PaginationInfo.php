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

class PaginationInfo
{
    /** @var int */
    protected $start;
    /** @var int */
    protected $count;
    /** @var int */
    protected $pageSize;
    /** @var int */
    protected $total;
    protected $pages;
    protected $currentPage;
    protected $pageDiffAround = 0;

    /**
     * PaginationInfo constructor.
     * @param int $start
     * @param int $count
     * @param int $pageSize
     * @param int $total
     * @param int $pagesAround
     */
    public function __construct(int $start = 0, int $count = 0, int $pageSize = 0, int $total = 0, int $pagesAround = 2)
    {
        $this->start = $start;
        $this->count = $count;
        $this->pageSize = $pageSize;
        $this->total = $total;
        $this->pageDiffAround = $pagesAround;
        $this->currentPage = $this->pageForItem($start);
    }

    public static function startItemForPage($page, $itemsPerPage) {
        if ($itemsPerPage < 0) return 0;
        if ($page <= 0) return 0;
        return ($page-1)*$itemsPerPage;
    }

    public function pageForItem($item) {
        if ($this->pageSize < 1) return 1;
        if ($item < 0) return 1;
        if ($item > $this->total) $item = $this->total;
        return (int)floor($item/$this->pageSize)+1;
    }

    /**
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @param int $start
     */
    public function setStart(int $start): void
    {
        $this->start = $start;
        $this->pages = null;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->count = $count;
        $this->pages = null;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     */
    public function setPageSize(int $pageSize): void
    {
        $this->pageSize = $pageSize;
        $this->pages = null;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    public function setTotal(int $total): void
    {
        $this->total = $total;
        $this->pages = null;
    }

    /**
     * @return int
     */
    public function getEnd(): int
    {
        return $this->start + $this->count - 1;
    }

    public function getIsFirst($page = -1) {
        if ($page < 0) $page = $this->currentPage;
        return $page <= 1;
    }

    public function getPrevious($page = -1) {
        if ($page < 0) $page = $this->currentPage;
        if ($this->getIsFirst($page)) return 1;
        return $page-1;
    }

    public function getIsLast($page = -1) {
        if ($page < 0) $page = $this->currentPage;
        return $page*$this->pageSize >= $this->total;
    }

    public function getNext($page = -1) {
        if ($page < 0) $page = $this->currentPage;
        if ($this->getIsLast($page)) return $page;
        return $page+1;
    }

    public function getFirst() {
        return 1;
    }

    public function getLast() {
        return $this->pageForItem($this->total);
    }

    public function getPages($currentPage = 0)
    {
        if ($this->pages == null) {
            $this->pages = [];
            $ps = ($this->pageSize < 1) ? $this->total : $this->pageSize;
            for ($p = 0; $p*$ps < $this->total; ++$p) {
                $this->pages[] = [
                    'page' => $p+1,
                    'start' => $p*$ps+1,
                    'end' => min(($p+1)*$ps, $this->total)
                ];
            }
        }

        if ($currentPage == 0) $currentPage = $this->currentPage;
        $pages = $this->pages;
        array_walk($pages, function(&$p) { $p['isCurrent'] = false; });
        if ($currentPage > 0) $pages[$currentPage-1]['isCurrent'] = true;

        return $pages;
    }

    public function getPagesAround($page = 0) {
        if ($page == 0) $page = $this->currentPage;
        $diff = $this->pageDiffAround;
        $pages = $this->getPages($page);
        $hasMore = $hasLess = false;
        if ($diff > 0 && count($pages) > $diff*2) {
            $start = $page - $diff - 1;
            $end = $page + $diff;

            if ($start < 0) $end -= $start;
            if ($end > count($pages)) $start -= $end - count($pages);

            $start = max($start, 0);
            $end = min($end, count($pages));

            $hasMore = $end < count($pages);
            $hasLess = $start > 0;
            $pages = array_slice($pages, $start, $end-$start);
        }
        return ['hasLess' => $hasLess, 'pages' => $pages, 'hasMore' => $hasMore];
    }
}
