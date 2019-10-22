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

namespace Ms3\Ms3CommerceFx\Persistence;

/**
 * Class DbBackend
 * Accesses mS3 Commerce dataTransfer DB connections
 * @package Ms3\Ms3CommerceFx\Persistence
 */
class DbBackend implements \TYPO3\CMS\Core\SingletonInterface
{
    private static $connections = [];
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $mainConnection = null;

    public function __construct()
    {
        $this->requireFile(\TYPO3\CMS\Core\Core\Environment::getPublicPath().'/dataTransfer/dataTransfer_config.php');
        $this->requireFile(\TYPO3\CMS\Core\Core\Environment::getPublicPath().'/dataTransfer/mS3CommerceDBAccess.php');
    }

    /**
     * @return \Doctrine\DBAL\Connection The connection to the main mS3 Commerce database
     * @throws \Exception On error connecting to database
     */
    public function getConnection()
    {
        if ($this->mainConnection == null) {
            $this->mainConnection = $this->getConnectionInternal('production');
        }
        return $this->mainConnection;
    }

    private function getConnectionInternal($type)
    {
        if ($type == 'production') {
            if (MS3COMMERCE_STAGETYPE == 'DATABASES') {
                $type = MS3COMMERCE_PRODUCTION_DB;
            } else if (MS3COMMERCE_STAGETYPE == 'TABLES') {
                throw new \Exception('TABLES stage type not supported');
            } else {
                throw new \Exception('Unsupported stage type');
            }
        } else if ($type == 'staging') {
            if (MS3COMMERCE_STAGETYPE == 'DATABASES') {
                $type = MS3COMMERCE_STAGE_DB;
            } else if (MS3COMMERCE_STAGETYPE == 'TABLES') {
                throw new \Exception('TABLES stage type not supported');
            } else {
                throw new \Exception('Unsupported stage type');
            }
        }

        $this->connectDb($type);
        return self::$connections[$type];
    }

    private function requireFile($filePath)
    {
        require_once $filePath;
    }

    private function connectDb($dbName)
    {
        if (array_key_exists($dbName, self::$connections)) {
            return;
        }

        $dbConnections = MS3C_DB_ACCESS();
        if (array_key_exists($dbName, $dbConnections)) {
            $connParams = $dbConnections[$dbName];

            // Translate parameters
            if (!array_key_exists('driver', $connParams)) $connParams['driver'] = 'mysqli';
            if (!array_key_exists('charset', $connParams)) $connParams['charset'] = 'utf8';
            $connParams['user'] = $connParams['username'];
            $connParams['dbname'] = $connParams['database'];

            $connection = \Doctrine\DBAL\DriverManager::getConnection($connParams);
            $connection->connect();
            self::$connections[$dbName] = $connection;
        } else {
            throw new \Exception('No database connection configured for database ' . $dbName);
        }
    }
}
