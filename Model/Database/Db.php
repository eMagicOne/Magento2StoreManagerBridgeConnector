<?php
/**
 *    This file is part of Magento Store Manager Connector.
 *
 *   Magento Store Manager Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Magento Store Manager Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Magento Store Manager Connector.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Emagicone\Connector\Model\Database;

use Emagicone\Connector\Logger\Logger;
use Emagicone\Connector\Model\AbstractModel;
use Magento\Backup\Model\ResourceModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Archive\Gz;
use Magento\Framework\Backup\Db\BackupFactory;
use Magento\Framework\Backup\Db\BackupInterface;
use Emagicone\Connector\Helper\Data;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\FileSystemException;
use Zend_Db_Statement_Exception;
use Zend_Db_Statement_Interface;

/**
 * Class Db
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class Db extends AbstractModel
{
    const BACKUP_NAME = 'bridge';

    const BACKUP_TYPE = 'db';

    const BUFFER_LENGTH = 102400;

    /**
     * @var ResourceConnection
     */
    private $_resourceConnection;

    /**
     * @var ResourceModel\Db
     */
    private $_resourceDb;

    /**
     * @var BackupFactory
     */
    private $_backupFactory;

    /**
     * @var
     */
    private $_time;

    /**
     * @var
     */
    private $_dumpGenerationParams;

    /**
     * @var string
     */
    private $_backupFileExtension = '.sql';

    /**
     * @var
     */
    private $_cachedData;

    /**
     * @var
     */
    private $_databaseConnection;

    // progress data for get_sql_progress
    /**
     * @var int
     */
    private $_currentTableNumber;

    /**
     * @var int
     */
    private $_allTablesForDumpCount;

    // tables filters for include_tables parameter handling
    /**
     * @var array
     */
    private $_exactTables;

    /**
     * @var array
     */
    private $_patternTables;

    /**
     * Db constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param ResourceModel\Db $resourceDb
     * @param BackupFactory $backupFactory
     * @param Logger $logger
     * @param Data $helper
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ResourceModel\Db $resourceDb,
        BackupFactory $backupFactory,
        Logger $logger,
        Data $helper
    ) {
        $this->_resourceConnection = $resourceConnection;
        $this->_resourceDb = $resourceDb;
        $this->_backupFactory = $backupFactory;
        parent::__construct(
            $logger,
            $helper
        );
    }

    /**
     * @return $this
     */
    public function setTime()
    {
        $this->_time = time();
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->_time;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setDumpGenerationParams($data)
    {
        $this->_dumpGenerationParams = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackupFileExtension()
    {
        return $this->_backupFileExtension;
    }

    /**
     * @param $value
     * @return $this
     */
    private function _setBackupFileExtension($value)
    {
        $this->_backupFileExtension = $value;
        return $this;
    }

    /**
     * @return ResourceModel\Db
     */
    public function getResource()
    {
        return $this->_resourceDb;
    }

    /**
     * @param null $maxSize
     * @return array|null
     * @throws FileSystemException
     */
    public function create($maxSize = null)
    {
        // from magento framework for preventing process kill
        set_time_limit(0);
        ignore_user_abort(true);

        $backup = $this->_backupFactory->createBackupModel()->setTime(
            $this->getTime()
        )->setType(
            self::BACKUP_TYPE
        )->setPath(
            $this->getBackupsDir()
        )->setName(
            self::BACKUP_NAME
        );
        $maxSize = $maxSize ? $maxSize * 1024 * 1024 : null;
        return $this->createBackup($backup, $maxSize);
    }

    /**
     * @param BackupInterface $backup
     * @param $maxSize
     * @return array|null
     */
    public function createBackup(BackupInterface $backup, $maxSize)
    {
        $backup->open(true);

        $this->getResource()->beginTransaction();

        [$tables, $views] = $this->getTablesAndViews();

        $size = 0;

        $this->_loadCachedData();

        foreach ($tables as $table) {
            $tableStatus = $this->getResource()->getTableStatus($table);
            if (!isset($this->_dumpGenerationParams['i'])
                && !isset($this->_dumpGenerationParams['limit'])
                && !isset($this->_dumpGenerationParams['multi_rows_length'])
            ) {
                $backup->write(
                    $this->getResource()->getTableHeader($table) . $this->getResource()->getTableDropSql($table) . "\n"
                );
                $backup->write($this->getResource()->getTableCreateSql($table, false) . "\n");
                if ($tableStatus->getRows()) {
                    $backup->write($this->getResource()->getTableDataBeforeSql($table));
                    if ($tableStatus->getDataLength() > self::BUFFER_LENGTH
                        && $tableStatus->getAvgRowLength() < self::BUFFER_LENGTH
                    ) {
                        $limit = floor(self::BUFFER_LENGTH / max($tableStatus->getAvgRowLength(), 1));
                        $multiRowsLength = ceil($tableStatus->getRows() / $limit);
                    } elseif ($maxSize !== null && $tableStatus->getDataLength() > $maxSize - $size) {
                        if ($tableStatus->getAvgRowLength() < $maxSize - $size) {
                            $limit = floor(($maxSize - $size) / max($tableStatus->getAvgRowLength(), 1));
                            $multiRowsLength = ceil($tableStatus->getRows() / $limit);
                        } else {
                            $limit = 1;
                            $multiRowsLength = $tableStatus->getRows();
                        }
                    } else {
                        $limit = $tableStatus->getRows();
                        $multiRowsLength = 1;
                    }
                    $i = 0;

                    $backup->write($this->getResource()->getTableDataAfterSql($table));
                }
            } else {
                $i = $this->_dumpGenerationParams['i'] + 1;
                $limit = $this->_dumpGenerationParams['limit'];
                $multiRowsLength = $this->_dumpGenerationParams['multi_rows_length'];
                unset($this->_dumpGenerationParams);
            }
            if ($tableStatus->getRows()) {
                for (; $i < $multiRowsLength; $i++) {
                    $tableInsert = $this->getResource()->getTableDataSql($table, $limit, $i * $limit);
                    $backup->write($tableInsert);
                    $size += mb_strlen($tableInsert, 'UTF-8');
                    unset($tableInsert);
                    $this->_logger->info('dump size: ' . $size);
                    $this->_logger->info($table);

                    $data = [
                        'table' => $table,
                        'limit' => $limit,
                        'multi_rows_length' => $multiRowsLength,
                        'i' => $i,
                        'progress' => $this->_currentTableNumber / $this->_allTablesForDumpCount,
                    ];
                    $this->_helper->saveToCache($data);

                    if ($maxSize !== null && $size >= $maxSize) {
                        $this->getResource()->commitTransaction();
                        $backup->close();
                        return $data;
                    }
                }
                unset($i);
                unset($multiRowsLength);
                unset($limit);
            }
            $this->_currentTableNumber++;
        }
        // Process views
        foreach ($views as $view) {
            $header = $this->getResource()->getTableHeader($view);
            $dropQuery = sprintf('DROP VIEW IF EXISTS %s;', $view);
            $createQuery = $this->_getDatabaseConnection()->fetchRow("SHOW CREATE TABLE {$view}")['Create View'];
            $backup->write("{$header}{$dropQuery}\n{$createQuery};\n");
        }

        $backup->write($this->getResource()->getTableForeignKeysSql());
        $backup->write($this->getResource()->getFooter());

        $this->getResource()->commitTransaction();

        $backup->close();
        $this->_helper->dropCachedData();
        return null;
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getBackupsDir()
    {
        return $this->_helper->getSaveDir();
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getBackupFilePath()
    {
        return $this->getBackupsDir() . '/' . $this->getBackupFilename();
    }

    /**
     * @return string
     */
    public function getBackupFilename()
    {
        $filename = $this->getTime() . '_' . self::BACKUP_TYPE;

        $name = self::BACKUP_NAME;

        if (!empty($name)) {
            $filename .= '_' . $name;
        }

        $filename .= $this->getBackupFileExtension();

        return $filename;
    }

    /**
     * @return bool
     * @throws FileSystemException
     */
    public function compress()
    {
        $archiveFilename = str_replace('.sql', '.gz', $this->getBackupFilePath());
        // create empty *.gz file
        $f = fopen($archiveFilename, 'w');
        fclose($f);

        // archive dump into *.gz file created earlier
        $gzPacker = new Gz();
        $gzPacker->pack(
            $this->getBackupFilePath(),
            $archiveFilename
        );
        unlink($this->getBackupFilePath());
        $this->_setBackupFileExtension('.gz');
        return true;
    }

    /**
     * @param $exactTables
     * @param $patternTables
     * @return $this
     */
    public function setTablesFilter($exactTables, $patternTables)
    {
        $this->_exactTables = $exactTables;
        $this->_patternTables = $patternTables;
        return $this;
    }

    /**
     * @return array
     */
    public function getTablesAndViews()
    {
        $allTables = $this->getResource()->getTables();
        // Get all views from current database
        $views = $this->_getDatabaseConnection()->fetchCol("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
        if (isset($this->_exactTables) && isset($this->_patternTables)) {
            $tables = array_intersect($allTables, $this->_exactTables);
            $tablesForPatternFilter = array_diff($allTables, $tables);
            foreach ($tablesForPatternFilter as $table) {
                if ($this->_checkTableByPatterns($table, $this->_patternTables)) {
                    $tables[] = $table;
                }
            }
        } else {
            $tables = $allTables;
            $excludedTables = $this->_helper->getExcludedTables();
            if (is_string($excludedTables)) {
                $excludedTables = explode(',', $excludedTables);
            }
            if (is_array($excludedTables)) {
                $tables = array_diff($tables, $excludedTables);
            }
        }

        $this->_allTablesForDumpCount = count($tables);
        sort($tables);
        // Leave views that exist in tables variable
        $views = array_intersect($views, $tables);
        // Delete views from tables variable
        $tables = array_diff($tables, $views);

        if (($this->_dumpGenerationParams)
            && is_array($this->_dumpGenerationParams)
            && array_key_exists('table', $this->_dumpGenerationParams)
            && array_key_exists('i', $this->_dumpGenerationParams)
            && array_key_exists('multi_rows_length', $this->_dumpGenerationParams)
        ) {
            $key = array_search($this->_dumpGenerationParams['table'], $tables);
            if ($this->_dumpGenerationParams['i'] == $this->_dumpGenerationParams['multi_rows_length']) {
                $key++;
            }
            $tables = array_slice($tables, $key);
        }

        $this->_currentTableNumber = $this->_allTablesForDumpCount - count($tables);

        return [$tables, $views];
    }

    /**
     * @param $table
     * @param array $patterns
     * @return bool
     */
    private function _checkTableByPatterns($table, array $patterns)
    {
        foreach ($patterns as $pattern) {
            if (strpos($table, str_replace('*', '', $pattern)) === 0) {
                return true;
            }
            continue;
        }
        return false;
    }

    /**
     * @return mixed
     * @throws Zend_Db_Statement_Exception
     */
    public function getDatabaseCharset()
    {
        $result = $this->runQuery('SELECT @@character_set_database AS charset')->fetch();
        return $result['charset'];
    }

    /**
     * @return mixed
     */
    public function getDatabaseConfig()
    {
        $result = $this->_getDatabaseConnection()->getConfig();
        return $result;
    }

    /**
     * @param $query
     * @return Zend_Db_Statement_Interface
     */
    public function runQuery($query)
    {
        return $this->_getDatabaseConnection()
            ->query($query);
    }

    /**
     * @return string
     */
    public function getTablePrefix()
    {
        $result = $this->_resourceConnection->getTablePrefix();
        return $result;
    }

    /**
     * @return AdapterInterface
     */
    private function _getDatabaseConnection()
    {
        if ($this->_databaseConnection === null) {
            $this->_databaseConnection = $this->_resourceConnection->getConnection();
        }
        return $this->_databaseConnection;
    }

    /**
     * @return void
     */
    private function _loadCachedData()
    {
        $this->_cachedData = $this->_helper->getDataFromCache();
    }
}
