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

namespace Emagicone\Connector\Plugin;

use Emagicone\Connector\Api\TaskInterface;

/**
 * Class PreChecks
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class PreChecks
{
    /**
     * Authentication and request data check
     *
     * @param TaskInterface $subject
     * @param callable $proceed
     */
    public function aroundProceed(TaskInterface $subject, callable $proceed)
    {
        $requestData = $subject->getRequestData();
        $checkAuth = $this->checkAuth($requestData, $subject);

        if ($subject->getHelper()->isEnabled() === false) {
            $subject->setResponseData(
                [
                    'response_code' => $subject::ERROR_CODE_COMMON,
                    'message' => 'Module is disabled'
                ]
            );
        } elseif ($checkAuth['status'] === false) {
            if (isset($checkAuth['session_key'])) {
                $subject->setResponseData(
                    [
                        'response_code' => $checkAuth['response_code'],
                        'session_key' => $checkAuth['session_key']
                    ]
                );
            } else {
                $subject->setResponseData(
                    [
                        'response_code' => $checkAuth['response_code'],
                        'message' => $checkAuth['message']
                    ]
                );
            }
        } elseif ($subject->dataValidation()) {
            try {
                $proceed();
            } catch (\Exception $exception) {
                $subject->setResponseData(
                    [
                        'response_data' => $subject::ERROR_CODE_COMMON,
                        'message' => $exception->getMessage()
                    ]
                );
            }
        } elseif ($subject->getResponseData() === null) {
            $subject->setResponseData(
                [
                    'response_code' => $subject::ERROR_CODE_COMMON,
                    'message' => 'Unknown error'
                ]
            );
        }
    }

    /**
     * @param string $hash
     * @return bool
     */
    private function isHashInOldFormat($hash)
    {
        return strlen($hash) === 32;
    }

    /**
     * @param $requestData
     * @param $subject
     * @return array
     */
    private function checkAuth($requestData, $subject)
    {
        $storedHash = $subject->getHelper()->getLoginAndPasswordHash();
        $cachedSessionKey = $subject->getHelper()->getDataFromCache($subject->getHelper()::SESSION_CACHE_ID);

        if (array_key_exists('key', $requestData)) {
            $key = (string)$requestData['key'];
            if (empty($key)) {
                return [
                    'status' => false,
                    'response_code' => $subject::ERROR_CODE_COMMON,
                    'message' => 'Request parameter "key" is empty'
                ];
            }
            if (!$key === $cachedSessionKey) {
                return [
                    'status' => false,
                    'response_code' => $subject::ERROR_CODE_SESSION_KEY,
                    'message' => 'Session key error'
                ];
            }
        } elseif (array_key_exists('hash', $requestData)) {
            $hash = (string)$requestData['hash'];

            if (empty($hash)) {
                return
                    [
                        'status' => false,
                        'response_code' => $subject::ERROR_CODE_COMMON,
                        'message' => 'Request parameter "hash" is empty'
                    ];
            }

            if ($hash !== $storedHash) {
                if ($this->isHashInOldFormat($hash)) {
                    return [
                        'status' => false,
                        'response_code' => $subject::ERROR_CODE_AUTHENTICATION,
                        'message' => 'The current Store Manager version is incompatible with Bridge Connector extension version. To solve this issue, please, update the Store Manager to the latest version.'
                    ];
                }
                return [
                    'status' => false,
                    'response_code' => $subject::ERROR_CODE_AUTHENTICATION,
                    'message' => 'Authentication error'
                ];
            }
            if ((string)$requestData['task'] !== 'get_version') {
                try {
                    $sessionKey = $subject->getHelper()->generateSessionKey();
                    return [
                        'status' => false,
                        'response_code' => $subject::SUCCESSFUL,
                        'session_key' => $sessionKey
                    ];
                } catch (\Exception $e) {
                    return [
                        'status' => false,
                        'response_code' => $subject::ERROR_CODE_COMMON,
                        'message' => $e->getMessage()
                    ];
                }
            }
        } else {
            return
                [
                    'status' => false,
                    'response_code' => $subject::ERROR_CODE_AUTHENTICATION,
                    'message' => 'Authentication error'
                ];
        }
        return ['status' => true];
    }
}
