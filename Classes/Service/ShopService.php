<?php


namespace Ms3\Ms3CommerceFx\Service;

use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Model\ShopInfo;
use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade;
use Ms3\Ms3CommerceFx\Persistence\StorageSession;
use TYPO3\CMS\Core\SingletonInterface;

class ShopService implements SingletonInterface
{
    /**
     * @var RepositoryFacade
     */
    private $repo;

    /**
     * @param RepositoryFacade $repo
     */
    public function injectObjectRepository(RepositoryFacade $repo) {
        $this->repo = $repo;
    }

    /**
     * @param int $id
     * @param ShopInfo $shop
     * @return bool
     */
    public function isIdInShop($id, $shop) {
        if (!$shop) return false;
        return $shop->containsId($id);
    }

    /**
     * @param int $id
     * @param int $shopId
     * @return bool
     */
    public function isIdInShopId($id, $shopId) {
        $targetShop = $this->getShop($shopId);
        return $this->isIdInShop($id, $targetShop);
    }

    /**
     * @param PimObject $object
     * @param ShopInfo $shop
     * @return bool
     */
    public function isObjectInShop($object, $shop) {
        return $this->isIdInShop($object->getId(), $shop);
    }

    /**
     * @param PimObject $object
     * @param int $shopId
     * @return bool
     */
    public function isObjectInShopId($object, $shopId) {
        return $this->isIdInShopId($object->getId(), $shopId);
    }

    /**
     * @param $object ?PimObject
     * @param $shopId int
     * @return PimObject|null
     */
    public function getObjectInShop(?PimObject $object, int $shopId) {
        if (!$object) return null;
        if (!$shopId) return $object;

        if ($this->isObjectInShopId($object, $shopId)) {
            return $object;
        }

        // Switch to new shop's context guid
        $menu = $this->repo->getObjectRepository()->getMenuById($object->getMenuId());
        $guid = $menu->getContextID();
        $guid = substr($guid, 0, strpos($guid, ':'));
        $guid .= ':'.$shopId;

        return $this->repo->getObjectByMenuGuid($guid);
    }

    /**
     * @param $object ?PimObject
     * @return PimObject|null
     */
    public function getObjectInCurrentShop(?PimObject $object) {
        return $this->getObjectInShop($object, $this->repo->getQuerySettings()->getShopId());
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder $query
     * @param string $field
     * @param int $shopId
     */
    public function addShopIdRestriction($query, $field, $shopId) {
        $shop = $this->getShop($shopId);
        $this->addShopRestriction($query, $field, $shop);
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder $query
     * @param string $field
     * @param ShopInfo $shop
     */
    public function addShopRestriction($query, $field, $shop) {
        $query->andWhere($query->expr()->and(
            $query->expr()->lte($shop->getStartId(), $field),
            $query->expr()->lt($field, $shop->getEndId())
        ));
    }

    private function getShop($id) {
        return $this->repo->getShopInfoRepository()->getByShopId($id);
    }
}
