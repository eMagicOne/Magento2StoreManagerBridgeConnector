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

namespace Emagicone\Connector\Api;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class General
 *
 * @author   Vitalii Drozd <vitaliidrozd@kommy.net>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
interface TaskInterface
{
    const REVISION = 1;

    const ERROR_OPERATION_ABORTED           = 18;
    const ERROR_CODE_COMMON                 = 19;
    const SUCCESSFUL                        = 20;
    const POST_ERROR_CHUNK_CHECKSUM_DIF     = 21;
    const POST_ERROR_SQL_INDEX              = 22;
    const ERROR_CODE_AUTHENTICATION         = 25;
    const ERROR_CODE_SESSION_KEY            = 26;
    const ERROR_GENERATE_STORE_FILE_ARCHIVE = 27;

    /**
     * Execute request`s requestData validation logic
     * Keep in mind, this method executes by around plugin before proceed() method
     *
     * @return bool
     */
    public function dataValidation();

    /**
     * Execute request`s logic
     * Keep in mind, there are around plugin for authentication check and requestData validation
     *
     * @return void
     */
    public function proceed();

    /**
     * @return AbstractHelper
     */
    public function getHelper();

    /**
     * @param array $requestData
     * @return TaskInterface
     */
    public function setRequestData($requestData);

    /**
     * Used in around plugin
     *
     * @return array
     */
    public function getRequestData();

    /**
     * @param $data
     * @return TaskInterface
     */
    public function setResponseData($data);

    /**
     * @return array|string|null
     */
    public function getResponseData();
}
