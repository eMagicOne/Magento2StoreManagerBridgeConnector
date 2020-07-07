<?php

namespace Emagicone\Connector\Setup;

use Emagicone\Connector\Helper\GlobalConstants;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

class UpgradeData implements UpgradeDataInterface
{
    protected $deploymentConfig;
    protected $scopeConfig;

    public function __construct(
        DeploymentConfig $deploymentConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->scopeConfig = $scopeConfig;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /*
         * From version 2.3.2 the required tables are added
         * This upgrade script set required tables as config record into 'core_config_data' table
         * and unset required tables from excluded_tables
         */
        if (version_compare($context->getVersion(), '2.3.2', '<')) {
            $tablePrefix = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);
            $requiredTables = GlobalConstants::REQUIRED_TABLES;
            $requiredTablesRegexp = GlobalConstants::REQUIRED_TABLES_REGEXP;
            if (!empty($tablePrefix)) {
                foreach ($requiredTables as $index => $table) {
                    $requiredTables[$index] = "{$tablePrefix}$table";
                }
                foreach ($requiredTablesRegexp as $index => $regexp) {
                    $requiredTablesRegexp[$index] = "/{$tablePrefix}$regexp/";
                }
            } else {
                foreach ($requiredTablesRegexp as $index => $regexp) {
                    $requiredTablesRegexp[$index] = "/$regexp/";
                }
            }
            $required_tables_data = [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => GlobalConstants::XML_PATH_EMAGICONE_CONNECTOR . '/settings/required_tables',
                'value' => implode(',', $requiredTables),
            ];
            $required_tables_regexp_data = [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => GlobalConstants::XML_PATH_EMAGICONE_CONNECTOR . '/settings/required_tables_regexp',
                'value' => implode(',', $requiredTablesRegexp) . ',' . GlobalConstants::SM_TABLES_REGEXP,
            ];
            $setup->getConnection()->insertOnDuplicate($setup->getTable('core_config_data'), $required_tables_data, ['value']);
            $setup->getConnection()->insertOnDuplicate($setup->getTable('core_config_data'), $required_tables_regexp_data, ['value']);
            $path = GlobalConstants::XML_PATH_EMAGICONE_CONNECTOR . '/settings/excluded_tables';
            $excludedTables = $this->scopeConfig->getValue($path);
            if (!empty($excludedTables)) {
                $excludedTables = explode(',', $excludedTables);
                $excludedTables = array_diff($excludedTables, $requiredTables);
                foreach ($requiredTablesRegexp as $regexp) {
                    $excludedTables = preg_grep($regexp, $excludedTables, PREG_GREP_INVERT);
                }
                $data = [
                    'scope' => 'default',
                    'scope_id' => 0,
                    'path' => $path,
                    'value' => implode(',', $excludedTables),
                ];
                $setup->getConnection()->insertOnDuplicate($setup->getTable('core_config_data'), $data, ['value']);
            }
        }
    }
}
