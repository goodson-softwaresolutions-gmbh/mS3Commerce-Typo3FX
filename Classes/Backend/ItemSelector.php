<?php


namespace Ms3\Ms3CommerceFx\Backend;


use Doctrine\DBAL\Query\QueryBuilder;
use Ms3\Ms3CommerceFx\Persistence\DbBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ItemSelector
{
    /** @var DbBackend */
    private $dbBackend;

    public function getItemListFlex(array &$config)
    {
        $pc = $this->getModuleConfig();
        $selection = $config['row']['config.rootStructureElement'];
        if (is_array($selection)) $selection = $selection[0];
        if (!empty($selection)) $pc['StructureElement'] = $selection;
        $pc['withEmpty'] = true;
        $items = $this->getItemList($pc);
        $config['items'] = $items;
    }

    public function getItemList(array $config)
    {
        $db = $this->getDbBackend();
        $q = $db->getConnection()->createQueryBuilder();

        $se = isset($config['StructureElement']) ? $config['StructureElement'] : '';
        $shopId = isset($config['ShopId']) ? intval($config['ShopId']) : 0;
        $shopRange = $this->getShop($shopId);
        $rootSe = $this->getRootStructureElement($shopId);
        if (empty($se) || $se == $rootSe) {
            $se = $rootSe;
            $config['withParent'] = false;
        }

        $q->select('g.Name, g.AuxiliaryName, g.AsimOid')
            ->from('Groups', 'g')
            ->innerJoin('g', 'StructureElement', 's', 's.Id = g.StructureElementId')
            ->where($q->expr()->eq('s.Name', $q->createNamedParameter($se)))
            ;

        $this->injectShopRestriction($q, $shopRange, 'g.');

        if (isset($config['withParent']) && $config['withParent']) {
            $q
                ->innerJoin('g', 'Menu', 'm', 'g.Id = m.GroupId')
                ->innerJoin('m', 'Menu', 'm1', 'm1.Id = m.ParentId')
                ->innerJoin('m1', 'Groups', 'p', 'm1.GroupId = p.Id');
            $q->addSelect("CONCAT(p.Name, ' ', p.AuxiliaryName, ' > ') AS ParentName");
            $q->addOrderBy('m.OrderPath');
        } else {
            $q->addSelect("'' as ParentName");
            $q->addOrderBy('g.Name');
        }


        $res = $q->execute();
        $items = [];
        if ($config['withEmpty']) {
            $items[] = ['',''];
        }
        while ($row = $res->fetch()) {
            $items[] = [
                $row['ParentName'].$row['Name']. ' ' . $row['AuxiliaryName'], $row['AsimOid']
            ];
        }
        return $items;
    }

    public function getStructureElementsFlex(array &$config)
    {
        $pc = $this->getModuleConfig();
        $default = $pc['StructureElement'];
        $selection = $config['row']['config.rootStructureElement'];
        if (is_array($selection)) $selection = $selection[0];
        if (empty($selection) && !empty($default))
        {
            $pc['withEmpty'] = false;
            $items = $this->getStructureElements($pc);
            $items = array_merge([["Default ($default)", '']], $items);
        } else {
            $pc['withEmpty'] = true;
            $items = $this->getStructureElements($pc);
        }

        $config['items'] = $items;
    }

    public function getStructureElements(array $config)
    {
        $db = $this->getDbBackend();
        $q = $db->getConnection()->createQueryBuilder();

        $shopId = isset($config['ShopId']) ? intval($config['ShopId']) : 0;
        $shopRange = $this->getShop($shopId);

        $q->select('Name')
            ->from('StructureElement')
            ->where('OrderNr >= 0')
            ->orderBy('OrderNr', 'DESC')
        ;
        $this->injectShopRestriction($q, $shopRange);

        $items = $q->execute()->fetchFirstColumn();
        $ret = [];
        if ($config['withEmpty']) {
            $ret[] = ['',''];
        }
        foreach ($items as $i) {
            $ret[] = [$i,$i];
        }
        return $ret;
    }

    public function getItemTree(array &$config)
    {

    }

    /**
     * @return DbBackend
     */
    private function getDbBackend()
    {
        // No DI if used as USER function in TypoScript, load via GU
        if (!$this->dbBackend) {
            $this->dbBackend = GeneralUtility::makeInstance(DbBackend::class);
        }
        return $this->dbBackend;
    }

    private function getShop($id)
    {
        $q = $this->getDbBackend()->getConnection()->createQueryBuilder();
        $q->select('s.StartId, s.EndId')
            ->from('ShopInfo', 's')
            ;

        if ($id == 0) {
            $q->where(
                's.Id = (SELECT MIN(Id) FROM ShopInfo)'
                );
        } else {
            $q->where(
                $q->expr()->eq('s.Id', $id)
            );
        }

        return $q->execute()->fetchAssociative();
    }

    private function getRootStructureElement($shopId)
    {
        $shopRange = $this->getShop($shopId);
        $db = $this->getDbBackend()->getConnection();

        $qs = $db->createQueryBuilder();
        $qs->select('MAX(OrderNr)')
            ->from('StructureElement')
            ;
        $this->injectShopRestriction($qs, $shopRange);

        $q = $db->createQueryBuilder();
        $q->select('s.Name')
            ->from('StructureElement', 's')
            ->where('s.OrderNr = ('.$qs->getSQL().')');
        $this->injectShopRestriction($q, $shopRange, 's.');

        return $q->execute()->fetchOne();
    }

    /**
     * @param QueryBuilder $q
     * @param $shopRange
     * @param string $prefix
     */
    private function injectShopRestriction($q, $shopRange, $prefix = '')
    {
        $q->andWhere($q->expr()->and(
            $q->expr()->lte($shopRange['StartId'], $prefix.'Id'),
            $q->expr()->lt($prefix.'Id', $shopRange['EndId'])
        ));
    }

    private function getModuleConfig()
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\Extbase\\Object\\ObjectManager');
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $pluginConfig = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'ms3commercefx', 'pi1');
        return isset($pluginConfig['ItemSelector']) ? $pluginConfig['ItemSelector'] : [];
    }
}
