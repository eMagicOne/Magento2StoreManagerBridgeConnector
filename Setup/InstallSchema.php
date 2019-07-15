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

namespace Emagicone\Connector\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Emagicone\Connector\Helper;

/**
 * Class InstallSchema
 * @package Emagicone\Connector\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $context->getVersion();
        $installer = $setup;
        $installer->startSetup();

        $this->createTableSessionKeys($installer);
        $this->createTableFailedLogin($installer);
        $this->insertDefaultData($installer);

        $installer->endSetup();
    }

    private function createTableSessionKeys($installer)
    {
        if (!$installer->tableExists(Helper\Constants::TABLE_SESSION_KEYS)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable(Helper\Constants::TABLE_SESSION_KEYS))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity'       => true,
                        'nullable'       => false,
                        'primary'        => true,
                        'unsigned'       => true,
                        'auto_increment' => true
                    ],
                    'Session ID'
                )
                ->addColumn(
                    'session_key',
                    Table::TYPE_TEXT,
                    100,
                    ['nullable' => false],
                    'Session Key'
                )
                ->addColumn(
                    'date_added',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => false],
                    'Session Data Added'
                )
                ->addColumn(
                    'last_activity',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => false],
                    'Session Latest Activity'
                );

            $installer->getConnection()->createTable($table);

            $installer->getConnection()->addIndex(
                $installer->getTable(Helper\Constants::TABLE_SESSION_KEYS),
                $installer->getIdxName(
                    $installer->getTable(Helper\Constants::TABLE_SESSION_KEYS),
                    ['session_key']
                ),
                ['session_key']
            );
        }
    }

    private function createTableFailedLogin($installer)
    {
        if (!$installer->tableExists(Helper\Constants::TABLE_FAILED_LOGIN)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable(Helper\Constants::TABLE_FAILED_LOGIN))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary' => true,
                        'unsigned' => true,
                        'auto_increment' => true
                    ],
                    'Failed Login ID'
                )
                ->addColumn(
                    'ip',
                    Table::TYPE_TEXT,
                    20,
                    ['nullable' => false],
                    'Failed Login IP'
                )
                ->addColumn(
                    'date_added',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => false],
                    'Failed Login Date'
                );

            $installer->getConnection()->createTable($table);

            $installer->getConnection()->addIndex(
                $installer->getTable(Helper\Constants::TABLE_FAILED_LOGIN),
                $installer->getIdxName(
                    $installer->getTable(Helper\Constants::TABLE_FAILED_LOGIN),
                    ['ip']
                ),
                ['ip']
            );
        }
    }

    private function insertDefaultData($installer)
    {
        $tmpDir = Helper\Tools::getObjectManager()->get('Magento\Framework\Module\Dir\Reader')
                ->getModuleDir('', 'Emagicone_Connector') . '/tmp';
        $tmpDir = str_replace(str_replace('\\', '/', BP), '', $tmpDir);

        $data = [
            'login'                 => Helper\Constants::DEFAULT_LOGIN,
            'password'              => Helper\Tools::getEncryptedData(Helper\Constants::DEFAULT_PASSWORD),
            'bridge_hash'           => md5(Helper\Constants::DEFAULT_LOGIN . Helper\Constants::DEFAULT_PASSWORD),
            'tmp_dir'               => $tmpDir,
            'allow_compression'     => Helper\Constants::DEFAULT_ALLOW_COMPRESSION,
            'compress_level'        => Helper\Constants::DEFAULT_COMPRESS_LEVEL,
            'limit_query_size'      => Helper\Constants::DEFAULT_LIMIT_QUERY_SIZE,
            'package_size'          => Helper\Constants::DEFAULT_PACKAGE_SIZE,
            'exclude_db_tables'     => implode(';', $this->getExcludeDbTablesDefault($installer)),
            'allowed_ips'           => Helper\Constants::DEFAULT_ALLOWED_IPS,
            'last_clear_date'       => time(),
        ];

        Helper\Tools::saveConfigValue(Helper\Constants::CONFIG_PATH_SETTINGS, serialize($data));

        // To save in cache
        Helper\Tools::getConfigValue(Helper\Constants::CONFIG_PATH_SETTINGS);
    }

    /**
     * @param $installer
     * @return array
     */
    private function getExcludeDbTablesDefault($installer)
    {
        $tables = explode(';', Helper\Constants::EXCLUDE_DB_TABLES_DEFAULT);

        foreach ($tables as $table) {
            $tables[] = $installer->getTable($table);
        }

        return $tables;
    }
}
