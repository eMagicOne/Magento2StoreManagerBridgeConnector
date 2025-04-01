<?php
namespace Emagicone\Connector\Setup\Patch\Data;

use Emagicone\Connector\Helper\GlobalConstants;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\ObjectManager;

class UDPatch implements DataPatchInterface
{
    private $moduleDataSetup;
    protected $deploymentConfig;
    protected $scopeConfig;
    protected $objectManager;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        DeploymentConfig $deploymentConfig,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->deploymentConfig = $deploymentConfig;
        $this->scopeConfig = $scopeConfig;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->objectManager = ObjectManager::getInstance();
        $productMetadata = $this->objectManager->get('Magento\Framework\App\ProductMetadataInterface');

        /*
         * From version 2.3.2 the required tables are added
         * This upgrade script set required tables as config record into 'core_config_data' table
         * and unset required tables from excluded_tables
         */
        //if (version_compare($productMetadata->getVersion(), '2.3.2', '>')) {
            $tablePrefix = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);
            $requiredTables = GlobalConstants::REQUIRED_TABLES;
            $requiredTablesRegexp = GlobalConstants::REQUIRED_TABLES_REGEXP;
            $excludedTables = GlobalConstants::EXCLUDED_TABLES;

            if (!empty($tablePrefix)) {
                foreach ($requiredTables as $index => $table) {
                    $requiredTables[$index] = "{$tablePrefix}$table";
                }
                foreach ($requiredTablesRegexp as $index => $regexp) {
                    $requiredTablesRegexp[$index] = "/{$tablePrefix}$regexp/";
                }
                foreach ($excludedTables as $index => $table) {
                    $excludedTables[$index] = "{$tablePrefix}$table";
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
            $excluded_tables_data = [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => GlobalConstants::XML_PATH_EMAGICONE_CONNECTOR . '/settings/excluded_tables',
                'value' => implode(',', $excludedTables),
            ];
            $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('core_config_data'), $required_tables_data, ['value']);
            $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('core_config_data'), $required_tables_regexp_data, ['value']);
            $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('core_config_data'), $excluded_tables_data, ['value']);
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
                $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('core_config_data'), $data, ['value']);
            }
       // }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
