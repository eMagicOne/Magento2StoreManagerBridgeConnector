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

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Emagicone\Connector\Helper\Tools;
use Emagicone\Connector\Helper\Constants;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (!Tools::getConfigValue(Constants::OPTIONS_NAME)) {
            $this->fillWithDefaultData($setup);
        }

        // To save in cache
        Tools::getConfigValue(Constants::CONFIG_PATH_SETTINGS);
    }

    private function fillWithDefaultData($setup)
    {
        $tmpDir = Tools::getObjectManager()->get('Magento\Framework\Module\Dir\Reader')
                ->getModuleDir('', 'Emagicone_Connector') . '/tmp';
        $tmpDir = str_replace(str_replace('\\', '/', BP), '', $tmpDir);

        $tables = explode(';', Constants::EXCLUDE_DB_TABLES_DEFAULT);
        $excludedTables = [];
        foreach ($tables as $index => $table) {
            $excludedTables[] = $setup->getTable($table);
        }

        $data = [
            'login'                 => Constants::DEFAULT_LOGIN,
            'password'              => Tools::getEncryptedData(Constants::DEFAULT_PASSWORD),
            'bridge_hash'           => md5(Constants::DEFAULT_LOGIN . Constants::DEFAULT_PASSWORD),
            'tmp_dir'               => $tmpDir,
            'allow_compression'     => Constants::DEFAULT_ALLOW_COMPRESSION,
            'compress_level'        => Constants::DEFAULT_COMPRESS_LEVEL,
            'limit_query_size'      => Constants::DEFAULT_LIMIT_QUERY_SIZE,
            'package_size'          => Constants::DEFAULT_PACKAGE_SIZE,
            'exclude_db_tables'     => implode(';', $excludedTables),
            'allowed_ips'           => Constants::DEFAULT_ALLOWED_IPS,
            'last_clear_date'       => time(),
        ];

        Tools::saveConfigValue(Constants::CONFIG_PATH_SETTINGS, serialize($data));
    }
}