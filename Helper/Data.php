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

namespace Emagicone\Connector\Helper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Data
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class Data extends AbstractHelper
{
    const CACHE_ID = 'emagicone_connector';

    const SESSION_CACHE_ID = 'emagicone_connector_session_key';

    const SAVE_DIR_NAME = '/emagicone';

    /**
     * @var CacheInterface
     */
    protected $_cache;

    /**
     * @var SerializerInterface
     */
    protected $_serializer;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var DirectoryList
     */
    private $_directoryList;

    /**
     * @var ConfigInterface
     */
    private $_config;

    /**
     * Data constructor.
     *
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $directoryList
     * @param Context $context
     * @param ConfigInterface $config
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        Context $context,
        ConfigInterface $config
    ) {
        $this->_cache = $cache;
        $this->_serializer = $serializer;
        $this->_scopeConfig = $scopeConfig;
        $this->_directoryList = $directoryList;
        $this->_config = $config;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->_getFieldValue('access', 'enable');
    }

    /**
     * @return mixed
     */
    public function getLogin()
    {
        return $this->_getFieldValue('access', 'login');
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->_getFieldValue('access', 'password');
    }

    /**
     * @return string
     */
    public function getLoginAndPasswordHash()
    {
        $login = $this->getLogin();
        $password = $this->getPassword();
        return hash('sha256', $login . $password);
    }

    /**
     * @return mixed
     */
    public function getPackageSize()
    {
        return (int)$this->_getFieldValue('settings', 'package_size');
    }

    /**
     * @return mixed
     */
    public function getExcludedTables()
    {
        return $this->_getFieldValue('settings', 'excluded_tables');
    }

    /**
     * @return array
     */
    public function getRequiredTables()
    {
        $requiredTables = $this->_getFieldValue('settings', 'required_tables');
        if (is_string($requiredTables) && !empty($requiredTables)) {
            return explode(',', $requiredTables);
        }
        return [];
    }

    /**
     * @return array
     */
    public function getRequiredTablesRegexp()
    {
        $requiredTablesRegexp = $this->_getFieldValue('settings', 'required_tables_regexp');
        if (is_string($requiredTablesRegexp) && !empty($requiredTablesRegexp)) {
            return explode(',', $requiredTablesRegexp);
        }
        return [];
    }

    /**
     * @param $groupId
     * @param $fieldId
     * @return mixed
     */
    private function _getFieldValue($groupId, $fieldId)
    {
        return $this->_scopeConfig->getValue(GlobalConstants::XML_PATH_EMAGICONE_CONNECTOR . '/' . $groupId . '/' . $fieldId);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getSaveDir()
    {
        $path = $this->_directoryList->getPath('tmp') . self::SAVE_DIR_NAME;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getMediaDir()
    {
        return $this->_directoryList->getPath('media');
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->_directoryList->getRoot();
    }

    /**
     * @param string $cacheId
     * @return array|bool|float|int|string|null
     */
    public function getDataFromCache($cacheId = self::CACHE_ID)
    {
        if ($cacheData = $this->_cache->load($cacheId)) {
            return $this->_serializer->unserialize($cacheData);
        }
        return null;
    }

    /**
     * @param string $cacheId
     */
    public function dropCachedData($cacheId = self::CACHE_ID)
    {
        $this->_cache->remove($cacheId);
    }

    /**
     * @param $data
     * @param string $cacheId
     * @param null $lifeTime
     */
    public function saveToCache($data, $cacheId = self::CACHE_ID, $lifeTime = null)
    {
        $this->_cache->save(
            $this->_serializer->serialize($data),
            $cacheId,
            [],
            $lifeTime
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function generateSessionKey()
    {
        $sessionKey = hash('sha256', random_bytes(64) . time());
        $this->saveToCache(
            $sessionKey,
            self::SESSION_CACHE_ID,
            60 * 60 * 2 // 2 hours cache lifetime
        );
        return $sessionKey;
    }

    /**
     * This function deletes all config values created by this module from core_config_data
     */
    public function deleteConfigs()
    {
        foreach (GlobalConstants::CONFIG_PATHS as $path) {
            $this->_config->deleteConfig($path);
        }
    }
}
