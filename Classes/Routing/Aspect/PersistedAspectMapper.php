<?php


namespace Ms3\Ms3CommerceFx\Routing\Aspect;

use Ms3\Ms3CommerceFx\Persistence\DbBackend;
use TYPO3\CMS\Core\Routing\Aspect\PersistedMappableAspectInterface;
use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PersistedAspectMapper implements StaticMappableAspectInterface, PersistedMappableAspectInterface
{
    // No Injection in Aspects... Created via simple GeneralUtility::makeInstance

    /** @var DbBackend */
    private $_db;
    /**
     * @return DbBackend
     */
    private function db() {
        if (!$this->_db) {
            $this->_db = GeneralUtility::makeInstance(DbBackend::class);
        }
        return $this->_db;
    }

    /**
     * @inheritDoc
     */
    public function generate(string $value): ?string
    {
        try {
            $q = $this->db()->getConnection()->createQueryBuilder();
            $q->select('*')
                ->from('RealURLMap')
                ->where($q->expr()->eq('asim_mapid', $q->createNamedParameter($value)));
            $res = $q->execute();
            if ($row = $res->fetch()) {
                $i = 0;
                $keys = [];
                while (array_key_exists("realurl_seg_$i", $row)) {
                    $keys[] = $row["realurl_seg_$i"];
                    $i++;
                }
                $keys[] = $row['realurl_seg_mapped'];
                $keys = array_filter($keys);
                return implode('/', $keys);
            }
        } catch(\Exception $e) {
        }

        // No match, return guid:int (urlencode!)
        return urlencode($value);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $value): ?string
    {
        // Can be a guid:int thing, so check for that first
        if (preg_match('/^[0-9A-Fa-f]{8}[-][0-9A-Fa-f]{4}[-][0-9A-Fa-f]{4}[-][0-9A-Fa-f]{4}[-][0-9A-Fa-f]{12}:\d+$/', $value)) {
            return urldecode($value);
        }

        $exploded = explode('/', $value);
        $value = array_pop($exploded);

        $q = $this->db()->getConnection()->createQueryBuilder();
        $q->select('*')
            ->from('RealURLMap')
            ->where($q->expr()->eq('realurl_seg_mapped', $q->createNamedParameter($value)));
        $res = $q->execute();
        if ($row = $res->fetch()) {
            return $row['asim_mapid'];
        } else {
            return '';
        }
    }
}
